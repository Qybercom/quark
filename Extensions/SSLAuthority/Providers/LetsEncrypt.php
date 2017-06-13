<?php
namespace Quark\Extensions\SSLAuthority\Providers;

use Quark\IQuarkModel;
use Quark\IQuarkStrongModel;
use Quark\Quark;
use Quark\QuarkArchException;
use Quark\QuarkCertificate;
use Quark\QuarkCertificateSAN;
use Quark\QuarkCipherKeyPair;
use Quark\QuarkCollection;
use Quark\QuarkDTO;
use Quark\QuarkFile;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkModel;
use Quark\QuarkURI;

use Quark\Extensions\SSLAuthority\IQuarkSSLAuthorityProvider;

/**
 * Class LetsEncrypt
 *
 * @package Quark\Extensions\SSLAuthority\Providers
 */
class LetsEncrypt implements IQuarkSSLAuthorityProvider {
	const URL_PRODUCTION = 'https://acme-v01.api.letsencrypt.org';
	const URL_STAGING = 'https://acme-staging.api.letsencrypt.org';
	const URL_AGREEMENT = 'https://letsencrypt.org/documents/LE-SA-v1.1.1-August-1-2016.pdf';

	const HEADER_NONCE = 'Replay-Nonce';

	const ALGO = 'RS256';
	const KEY_TYPE = 'RSA';
	const SIGNATURE = 'SHA256';
	const CHALLENGE = '/.well-known/acme-challenge/';

	/**
	 * @var string $_api = self::URL_PRODUCTION
	 */
	private $_api = self::URL_PRODUCTION;

	/**
	 * @var QuarkCipherKeyPair $_key
	 */
	private $_key;

	/**
	 * @var string $_nonce = ''
	 */
	private $_nonce = '';

	/**
	 * @var string $_challenge = ''
	 */
	private $_challenge = '';

	/**
	 * @var string $_check = ''
	 */
	private $_check = '';

	/**
	 * @param string $message = ''
	 * @param bool $openssl = true
	 *
	 * @return bool
	 */
	private static function _error ($message = '', $openssl = true) {
		Quark::Log('[SSLAuthority.LetsEncrypt] ' . $message . ($openssl ? '. OpenSSL error: "' . openssl_error_string() . '".' : ''), Quark::LOG_WARN);
		return false;
	}

	/**
	 * @param object $options
	 *
	 * @return mixed
	 */
	public function SSLAuthorityOptions ($options) {
		if (isset($options->Staging))
			$this->_api = $options->Staging ? self::URL_STAGING : self::URL_PRODUCTION;

		if (isset($options->AccountKey))
			$this->_key = new QuarkCipherKeyPair($options->AccountKey);

		if (isset($options->AccountPassphrase) && $this->_key instanceof QuarkCipherKeyPair)
			$this->_key->Passphrase($options->AccountPassphrase);

		$this->_key = $this->_key instanceof QuarkCipherKeyPair
			? $this->_key->Load()
			: QuarkCipherKeyPair::GenerateNew();

		$this->_challenge = isset($options->WellKnown)
			? $options->WellKnown
			: Quark::Host() . self::CHALLENGE;
	}

	/**
	 * @param QuarkCertificate $certificate
	 *
	 * @return QuarkCertificate
	 */
	public function SSLAuthorityCertificateRequest (QuarkCertificate $certificate) {
		return $this->SSLAuthorityCertificateRequestRaw(
			$certificate->SigningRequest(),
			$certificate->Key()->PrivateKey(false),
			$certificate->subjectAltName,
			$certificate->Passphrase()
		);
	}

	/**
	 * @param string $csr
	 * @param resource $key
	 * @param string $altName
	 * @param string $passphrase
	 *
	 * @return QuarkCertificate
	 */
	public function SSLAuthorityCertificateRequestRaw ($csr, $key, $altName, $passphrase) {
		$account = $this->AccountCreate(array('mailto:saniafd93@gmail.com'));
		//print_r($account->Data());

		$sans = QuarkCertificateSAN::FromAltName($altName);
		//print_r($sans);

		foreach ($sans as $san) {
			$challenges = $this->ChallengeRequest($san->Type(), $san->Value());
			if ($challenges == null) continue;
			//print_r($challenges);

			$challenge = $challenges->SelectOne(array(
				'type' => LetEncryptChallenge::TYPE_HTTP
			));

			if (!$this->ChallengeAccept($challenge)) continue;
			if (!$this->ChallengeCheck($challenge)) continue;
		}

		$certificate = $this->Sign($this->CSR($csr));
		$certificate->Passphrase($passphrase);

		$out = '';
		if (!openssl_pkey_export($key, $out, $passphrase, $certificate->Key()->Config()))
			return self::_error('Cannot export private key');

		$certificate->Content($certificate->Content() . "\r\n" . $out);

		return $certificate;
	}

	/**
	 * @param QuarkCertificate $certificate
	 *
	 * @return QuarkCertificate
	 */
	public function SSLAuthorityCertificateRenew (QuarkCertificate $certificate) {
		// TODO: Implement SSLAuthorityCertificateRenew() method.
	}

	/**
	 * @param string $csr = ''
	 *
	 * @return string
	 */
	public function CSR ($csr = '') {
		return preg_match('#REQUEST-----(.*)-----END#s', $csr, $matches)
			? trim(QuarkURI::Base64Encode(base64_decode($matches[1])))
			: '';
	}

	/**
	 * @return array
	 */
	public function AccountHeader () {
		$details = $this->_key->Details();

		return array(
			'e' => QuarkURI::Base64Encode($details['rsa']['e']),
			'kty' => self::KEY_TYPE,
			'n' => QuarkURI::Base64Encode($details['rsa']['n']),
		);
	}

	/**
	 * @param string[] $contact = []
	 *
	 * @return QuarkDTO
	 */
	public function AccountCreate ($contact = []) {
		$data = array(
			'resource' => 'new-reg',
			'agreement' => self::URL_AGREEMENT
		);

		if ($contact)
			$data['contact'] = $contact;

		return $this->API('/acme/new-reg', $data);
	}

	/**
	 * https://community.letsencrypt.org/t/dns-name-does-not-have-enough-labels/5036/7?u=alex025
	 *
	 * @param string $type = ''
	 * @param string $value = ''
	 *
	 * @return QuarkCollection|LetEncryptChallenge[]
	 */
	public function ChallengeRequest ($type = '', $value = '') {
		if (strlen($type) == 0) return self::_error('Challenge: Type must not be empty', false);
		if (strlen($value) == 0) return self::_error('Challenge: Value must not be empty', false);

		$response = $this->API('/acme/new-authz', array(
			'resource' => 'new-authz',
			'identifier' => array(
				'type' => strtolower($type),
				'value' => $value
			)
		));

		if (!isset($response->challenges))
			return self::_error('ChallengeRequest: There are no challenges for ' . $type . ':' . $value . ': ' . print_r($response->Data(), true), false);

		$this->_check = $response->Header(QuarkDTO::HEADER_LOCATION);

		$out = new QuarkCollection(new LetEncryptChallenge());
		$out->PopulateModelsWith($response->challenges);

		return $out;
	}

	/**
	 * @param QuarkModel|LetEncryptChallenge $challenge = null
	 *
	 * @return bool
	 */
	public function ChallengeAccept (QuarkModel $challenge = null) {
		if ($challenge == null)
			return self::_error('ChallengeAccept: Challenge must not be null', false);
		//print_r($challenge);

		$acme = new QuarkFile($this->_challenge . $challenge->token);
		$acme->Content($challenge->Payload($this->AccountHeader()));

		if (!$acme->SaveContent())
			return self::_error('ChallengeAccept: Cannot save challenge to "' . $acme->Location() . '"', false);

		return true;
	}

	/**
	 * @param QuarkModel|LetEncryptChallenge $challenge = null
	 *
	 * @return bool
	 */
	public function ChallengeCheck (QuarkModel $challenge = null) {
		if ($challenge == null)
			return self::_error('ChallengeCheck: Challenge must not be null', false);

		$response = $this->API($challenge->uri, array(
			'resource' => 'challenge',
			'type' => LetEncryptChallenge::TYPE_HTTP,
			'keyAuthorization' => $challenge->Payload($this->AccountHeader()),
			'token' => $challenge->token
		));

		//print_r($response);

		if (!isset($response->status) || $response->status == LetEncryptChallenge::STATUS_INVALID)
			return self::_error('ChallengeCheck: Challenge check error: ' . print_r($response, true), false);

		return $this->APICheck($this->_check, function ($res) {
			return isset($res->status) && $res->status != LetEncryptChallenge::STATUS_PENDING;
		});
	}

	/**
	 * @param string $csr = ''
	 *
	 * @return QuarkCertificate
	 */
	public function Sign ($csr = '') {
		if (!$csr)
			return self::_error('Sign: CSR must not be empty', false);

		$response = $this->API('/acme/new-cert', array(
			'resource' => 'new-cert',
			'csr' => $csr
		));
		//print_r($response);
		if ($response->Status() != QuarkDTO::STATUS_201_CREATED)
			return self::_error('Sign: New certificate request returned unexpected response: ' . print_r($response, true), false);

		$this->_check = $response->Header(QuarkDTO::HEADER_LOCATION);

		$out = array();

		/**
		 * @var QuarkDTO $result
		 */
		$result = null;
		$ok = $this->APICheck($this->_check, function (QuarkDTO $res) use (&$result) {
			$result = $res;

			return $res->Status() == QuarkDTO::STATUS_200_OK;
		});
		if (!$ok) return true;
		//print_r($result);
		$out[] = $this->PEMFromRaw($result->RawData());

		$cert = $this->APIRaw(preg_replace('#<(.*)>;rel="up"#Uis', '$1', $result->Header('Link')));
		//print_r($cert);
		if (!$cert) return false;
		$out[] = $this->PEMFromRaw($cert->RawData());

		$certificate = new QuarkCertificate();
		$certificate->Content(implode("\r\n", $out));
		$certificate->Key($this->_key);

		return $certificate;
	}

	/**
	 * @param string $raw = ''
	 *
	 * @return string
	 */
	public function PEMFromRaw ($raw = '') {
		return '-----BEGIN CERTIFICATE-----' . "\r\n" . chunk_split(base64_encode($raw), 64, "\r\n") . '-----END CERTIFICATE-----' . "\r\n";
	}

	/**
	 * @param string $uri
	 *
	 * @return bool|QuarkDTO
	 */
	public function APIRaw ($uri = '') {
		$response = QuarkHTTPClient::To(
			$uri,
			QuarkDTO::ForGET(new QuarkJSONIOProcessor()),
			new QuarkDTO(new QuarkJSONIOProcessor())
		);

		return $response ? $response : self::_error('APICheck: Requested URI "' . $uri . '" network error', false);
	}

	/**
	 * @param string $uri = ''
	 * @param callable $checker = null
	 * @param int $interval = 1 second
	 *
	 * @return bool
	 */
	public function APICheck ($uri = '', callable $checker = null, $interval = 1) {
		if ($uri == '')
			return self::_error('APICheck: URI must not be empty', false);

		if ($checker == null)
			return self::_error('APICheck: Checker must not be null', false);

		$valid = false;

		while (!$valid) {
			$response = $this->APIRaw($uri);
			if (!$response) return false;

			$valid = $checker($response, $uri, $interval);
			if (!$valid) sleep($interval);
		}

		return true;
	}

	/**
	 * @param string $uri = ''
	 * @param array $payload = []
	 *
	 * @return QuarkDTO
	 */
	public function API ($uri = '', $payload = []) {
		if ($this->_nonce == '') {
			$nonce = QuarkHTTPClient::To($this->_api . '/directory', QuarkDTO::ForGET(), new QuarkDTO(new QuarkJSONIOProcessor()));

			if (!$nonce)
				return self::_error('API: Cannot get nonce', false);

			$this->_nonce = $nonce->Header(self::HEADER_NONCE);
		}

		if ($this->_key == null || !$this->_key->Loaded())
			return self::_error('API: Cannot load private key', false);

		$header = array(
			'alg' => self::ALGO,
			'jwk' => $this->AccountHeader()
		);

        $protected = $header;
		$protected['nonce'] = $this->_nonce;

		$payload64 = QuarkURI::Base64Encode(str_replace('\\/', '/', json_encode($payload)));
		$protected64 = QuarkURI::Base64Encode(json_encode($protected));

        $sign = openssl_sign($protected64 . '.' . $payload64, $signature, $this->_key->PrivateKey(false), self::SIGNATURE);
		if (!$sign)
			return self::_error('API: Cannot sign request');

		$req = QuarkDTO::ForPOST(new QuarkJSONIOProcessor());
		$req->Data(array(
            'header' => $header,
            'protected' => $protected64,
            'payload' => $payload64,
            'signature' => QuarkURI::Base64Encode($signature)
        ));

		$res = QuarkHTTPClient::To(preg_match('~^http~', $uri) ? $uri : $this->_api . $uri, $req, new QuarkDTO(new QuarkJSONIOProcessor()));

		if (!$res)
			return self::_error('API: Cannot perform api call ' . $uri, false);

		$this->_nonce = $res->Header(self::HEADER_NONCE);

		return $res;
	}
}

/**
 * Class LetEncryptChallenge
 *
 * @property string $ype = self::TYPE_HTTP
 * @property string $status = self::STATUS_PENDING
 * @property string $uri
 * @property string $token
 *
 * @package Quark\Extensions\SSLAuthority\Providers
 */
class LetEncryptChallenge implements IQuarkModel, IQuarkStrongModel {
	const TYPE_HTTP = 'http-01';
	const TYPE_DNS = 'dns-01';
	const TYPE_TLS_SNI = 'tls-sni-01';

	const STATUS_VALID = 'valid';
	const STATUS_PENDING = 'pending';
	const STATUS_INVALID = 'invalid';

	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'type' => self::TYPE_HTTP,
			'status' => self::STATUS_PENDING,
			'uri' => '',
			'token' => ''
		);
	}

	/**
	 * @return mixed
	 */
	public function Rules () {
		// TODO: Implement Rules() method.
	}

	/**
	 * @param array $header
	 *
	 * @return string
	 */
	public function Payload ($header) {
		return $this->token . '.' . QuarkURI::Base64Encode(hash('sha256', json_encode($header), true));
	}
}

/**
 * Class LetsEncrypt
 *
 * @package Quark\Extensions\SSLAuthority\Providers
 */
class LetsEncrypt1 implements IQuarkSSLAuthorityProvider {
	const URL_PRODUCTION = 'https://acme-v01.api.letsencrypt.org';
	const URL_STAGING = 'https://acme-staging.api.letsencrypt.org';
	const URL_AGREEMENT = 'https://letsencrypt.org/documents/LE-SA-v1.1.1-August-1-2016.pdf';

	const HEADER_NONCE = 'Replay-Nonce';

	/**
	 * @var string $_api = self::URL_PRODUCTION
	 */
	private $_api = self::URL_PRODUCTION;

	/**
	 * @var QuarkFile $_accountPrivate
	 */
	private $_accountPrivate;

	/**
	 * @var QuarkFile $_accountPublic
	 */
	private $_accountPublic;

	/**
	 * @var string $_accountPassphrase
	 */
	private $_accountPassphrase;

	/**
	 * @var string $_nonce = ''
	 */
	private $_nonce = '';

	/**
	 * @param string $message = ''
	 * @param bool $openssl = true
	 *
	 * @return null
	 */
	private static function _error ($message = '', $openssl = true) {
		Quark::Log('[SSLAuthority.LetsEncrypt] ' . $message . ($openssl ? '. OpenSSL error: "' . openssl_error_string() . '".' : ''), Quark::LOG_WARN);
		return false;
	}

	/**
	 * @param bool $store = true
	 *
	 * @return bool
	 */
	public function AccountKeyCreate ($store = true) {
		$config = QuarkCertificate::OpenSSLConfig();

		$key = openssl_pkey_new($config);

		if (!$key)
			return self::_error('AccountKey: Cannot generate new private key');

		$_private = '';
		if (!openssl_pkey_export($key, $_private, $this->_accountPassphrase, $config))
			return self::_error('AccountKey: Cannot export private key');

        $_public = openssl_pkey_get_details($key);

		$this->_accountPrivate->Content($_private);
		$this->_accountPublic->Content($_public['key']);

		return $store ? $this->_accountPrivate->SaveContent() && $this->_accountPublic->SaveContent() : true;
	}

	/**
	 * @var array $_header = []
	 */
	private $_header = array();

	/**
	 * @var resource $_key = null
	 */
	private $_key = null;

	/**
	 * @var array $_details = []
	 */
	private $_details = array();

	/**
	 * @return bool|null
	 * @throws QuarkArchException
	 */
	public function AccountKeyRead () {
		if ($this->_accountPrivate == null)
			return self::_error('API: Private key is null. This may be caused due to errors.', false);

		$pem = $this->_accountPrivate->Load();
		$this->_key = openssl_pkey_get_private($pem->Content(), $this->_accountPassphrase);
		if (!$this->_key)
			return self::_error('API: Cannot get private key');

		$this->_details = openssl_pkey_get_details($this->_key);
		$this->_header = array(
			'alg' => 'RS256',
			'jwk' => array(
				'e' => QuarkURI::Base64Encode($this->_details['rsa']['e']),
				'kty' => 'RSA',
				'n' => QuarkURI::Base64Encode($this->_details['rsa']['n']),
			)
		);

		return true;
	}

	/**
	 * @param string $uri = ''
	 * @param array $payload = []
	 *
	 * @return QuarkDTO
	 */
	public function API ($uri = '', $payload = []) {
		if ($this->_nonce == '') {
			$nonce = QuarkHTTPClient::To($this->_api . '/directory', QuarkDTO::ForGET(), new QuarkDTO(new QuarkJSONIOProcessor()));

			if (!$nonce)
				return self::_error('API: Cannot get nonce', false);

			$this->_nonce = $nonce->Header(self::HEADER_NONCE);
		}

        $protected = $this->_header;
		$protected['nonce'] = $this->_nonce;

		$payload64 = QuarkURI::Base64Encode(str_replace('\\/', '/', json_encode($payload)));
		$protected64 = QuarkURI::Base64Encode(json_encode($protected));

        $sign = openssl_sign($protected64 . '.' . $payload64, $signature, $this->_key, "SHA256");
		if (!$sign)
			return self::_error('API: Cannot sign request');

		$req = QuarkDTO::ForPOST(new QuarkJSONIOProcessor());
		$req->Data(array(
            'header' => $this->_header,
            'protected' => $protected64,
            'payload' => $payload64,
            'signature' => QuarkURI::Base64Encode($signature)
        ));

		$res = QuarkHTTPClient::To($this->_api . $uri, $req, new QuarkDTO(new QuarkJSONIOProcessor()));

		if (!$res)
			return self::_error('API: Cannot perform api call ' . $uri, false);

		$this->_nonce = $res->Header(self::HEADER_NONCE);

		return $res;
	}

	public function DomainSign ($domains = []) {

	}

	/**
	 * @param string $domain = ''
	 *
	 * @return mixed|null
	 */
	public function DomainChallenge ($domain = '') {
		if (strlen($domain) == 0)
			return self::_error('DomainChallenge: Domain must not be empty', false);

		$response = $this->API('/acme/new-authz', array(
			'resource' => 'new-authz',
			'identifier' => array(
				'type' => 'dns',
				'value' => $domain
			)
		));

		if (!isset($response->challenges))
			return self::_error('DomainChallenge: There are no challenges for ' . $domain . print_r($response->Data(), true), false);

		return $response->challenges;
	}

	/**
	 * @param string[] $contact = []
	 *
	 * @return QuarkDTO
	 */
	public function AccountCreate ($contact = []) {
		$data = array(
			'resource' => 'new-reg',
			'agreement' => self::URL_AGREEMENT
		);

		if ($contact)
			$data['contact'] = $contact;

		return $this->API('/acme/new-reg', $data);
	}

	/**
	 * @param object $options
	 *
	 * @return mixed
	 */
	public function SSLAuthorityOptions ($options) {
		if (isset($options->Staging))
			$this->_api = $options->Staging ? self::URL_STAGING : self::URL_PRODUCTION;

		if (isset($options->AccountPrivate))
			$this->_accountPrivate = new QuarkFile($options->AccountPrivate);

		if (isset($options->AccountPublic))
			$this->_accountPublic = new QuarkFile($options->AccountPublic);

		if (isset($options->AccountPassphrase))
			$this->_accountPassphrase = $options->AccountPassphrase;
	}

	/**
	 * @param QuarkCertificate $certificate
	 *
	 * @return QuarkCertificate
	 */
	public function SSLAuthorityCertificateRequest (QuarkCertificate $certificate) {
		return $this->SSLAuthorityCertificateRequestRaw(
			$certificate->SigningRequest(),
			$certificate->Key()->PrivateKey(false),
			$certificate->subjectAltName,
			$certificate->Passphrase()
		);
	}

	/**
	 * @param string $csr
	 * @param resource $key
	 * @param string $altName
	 * @param string $passphrase
	 *
	 * @return QuarkCertificate
	 */
	public function SSLAuthorityCertificateRequestRaw ($csr, $key, $altName, $passphrase) {
		$this->AccountKey();

		$account = $this->AccountCreate(array('mailto:saniafd93@gmail.com'));

		print_r($account);
	}

	/**
	 * @param QuarkCertificate $certificate
	 *
	 * @return QuarkCertificate
	 */
	public function SSLAuthorityCertificateRenew (QuarkCertificate $certificate) {
		// TODO: Implement SSLAuthorityCertificateRenew() method.
	}
}