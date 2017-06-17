<?php
namespace Quark\Extensions\SSLAuthority\Providers;

use Quark\IQuarkGetService;
use Quark\IQuarkModel;
use Quark\IQuarkStrongModel;

use Quark\Quark;
use Quark\QuarkServiceBehavior;
use Quark\QuarkSession;
use Quark\QuarkURI;
use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkCertificate;
use Quark\QuarkCertificateSAN;
use Quark\QuarkCipherKeyPair;
use Quark\QuarkModel;
use Quark\QuarkCollection;
use Quark\QuarkFile;
use Quark\QuarkObject;

use Quark\Extensions\SSLAuthority\IQuarkSSLAuthorityProvider;

/**
 * https://github.com/analogic/lescript
 *
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
	 * @var string $_accountPassphrase = null
	 */
	private $_accountPassphrase = null;

	/**
	 * @var bool $_accountMultiple = false
	 */
	private $_accountMultiple = false;

	/**
	 * @var string $_keyAlgo = QuarkCertificate::ALGO_SHA512
	 */
	private $_accountAlgo = QuarkCertificate::ALGO_SHA512;

	/**
	 * @var int $_accountLength = QuarkCertificate::DEFAULT_BITS
	 */
	private $_accountLength = QuarkCertificate::DEFAULT_BITS;

	/**
	 * @var int $_accountType = OPENSSL_KEYTYPE_RSA
	 */
	private $_accountType = OPENSSL_KEYTYPE_RSA;

	/**
	 * @var string $_nonce = ''
	 */
	private $_nonce = '';

	/**
	 * @var string $_wellKnownChallenge = null
	 */
	private $_wellKnownChallenge = null;

	/**
	 * @var bool $_wellKnownServe = true
	 */
	private $_wellKnownServe = true;

	/**
	 * @var string $_check = ''
	 */
	private $_check = '';

	/**
	 * @var string $_contact = ''
	 */
	private $_contact = '';

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
	 * Options:
	 *  - Staging           bool   false  - Indicates using of staging/production API
	 *  - AccountKey        string        - Location of account private key
	 *  - AccountPassphrase string        - Optional passphrase for account private key
	 *  - Contact           string        - E-mail address for contact
	 *  - MultipleUse       bool   false  - Indicates using of already defined account
	 *  - AccountKeyAlgo    string sha512 - Account private key generation algorithm
	 *  - AccountKeyLength  int    4096   - Account private key length in bits
	 *  - AccountKeyType    string RSA    - Account private key type
	 *  - WellKnown         string        - Default location of ACME challenges. See self::CHALLENGE
	 *
	 * @param object $options
	 *
	 * @return mixed
	 */
	public function SSLAuthorityOptions ($options) {
		if (isset($options->Staging))
			$this->_api = $options->Staging ? self::URL_STAGING : self::URL_PRODUCTION;

		if (isset($options->AccountKey))
			$this->_key = new QuarkCipherKeyPair($options->AccountKey);

		if (isset($options->AccountPassphrase)) {
			$this->_accountPassphrase = $options->AccountPassphrase;

			if ($this->_key instanceof QuarkCipherKeyPair)
				$this->_key->Passphrase($options->AccountPassphrase);
		}

		if (isset($options->Contact))
			$this->_contact = $options->Contact;

		if (isset($options->MultipleUse))
			$this->_accountMultiple = $options->MultipleUse;

		if (isset($options->AccountKeyAlgo))
			$this->_accountAlgo = $options->AccountKeyAlgo;

		if (isset($options->AccountKeyLength))
			$this->_accountLength = (int)$options->AccountKeyLength;

		if (isset($options->AccountKeyType))
			$this->_accountType = QuarkObject::ConstValue('OPENSSL_KEYTYPE_' . $options->AccountKeyType);

		if (isset($options->WellKnownServe))
			$this->_wellKnownServe = $options->WellKnownServe;

		$this->_wellKnownChallenge = isset($options->WellKnown)
			? $options->WellKnown
			: Quark::Host() . self::CHALLENGE;

		if ($this->_wellKnownServe) {
			$acme = new LetsEncryptWellKnown();
			$acme->ListenURL('/acme', self::CHALLENGE);
		}
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
		if (!$this->AccountConfig()) return null;

		$this->AccountCreate($this->_contact ? array('mailto:' . $this->_contact) : array());

		$sans = QuarkCertificateSAN::FromAltName($altName);

		foreach ($sans as $san) {
			$challenges = $this->ChallengeRequest($san->Type(), $san->Value());
			if ($challenges == null) continue;

			$challenge = $challenges->SelectOne(array(
				'type' => LetEncryptChallenge::TYPE_HTTP
			));

			if (!$this->ChallengeAccept($challenge)) continue;
			if (!$this->ChallengeCheck($challenge)) continue;
		}

		$certificate = $this->Sign($this->CSR($csr));
		if (!$certificate) return null;

		$certificate->Passphrase($passphrase);

		$out = '';
		if (!openssl_pkey_export($key, $out, $passphrase, $certificate->Key()->Config()))
			return self::_error('Cannot export private key');

		$certificate->Content($certificate->Content() . "\r\n" . $out);
		$certificate->Key(QuarkCipherKeyPair::FromContent($out, $passphrase));

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
	 * @return bool
	 */
	public function AccountConfig () {
		if ($this->_accountMultiple && $this->_key instanceof QuarkCipherKeyPair && !$this->_key->Exists() && !$this->_key->SaveContent())
			return self::_error('Cannot init account private key');

		$this->_key = $this->_key instanceof QuarkCipherKeyPair && $this->_key->Exists()
			? $this->_key->Load()
			: QuarkCipherKeyPair::GenerateNew($this->_accountPassphrase, $this->_accountAlgo, $this->_accountLength, $this->_accountType);

		$init = $this->_key->Init();

		if (!$init)
			$this->_key->Generate($this->_key->Passphrase(), $this->_accountAlgo, $this->_accountLength, $this->_accountType);

		if ($this->_accountMultiple && !$init && !$this->_key->SaveContent())
			return self::_error('Cannot export account private key');

		return true;
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

		$acme = new QuarkFile($this->_wellKnownChallenge . $challenge->token);
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
		$out[] = $this->PEMFromRaw($result->RawData());

		$cert = $this->APIRaw(preg_replace('#<(.*)>;rel="up"#Uis', '$1', $result->Header('Link')));
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
 * Class LetsEncryptWellKnown
 *
 * @package Quark\Extensions\SSLAuthority\Providers
 */
class LetsEncryptWellKnown implements IQuarkGetService {
	use QuarkServiceBehavior;

	/**
	 * @param QuarkDTO $request
	 * @param QuarkSession $session
	 *
	 * @return mixed
	 */
	public function Get (QuarkDTO $request, QuarkSession $session) {
		$challenge = new QuarkFile(Quark::Host() . LetsEncrypt::CHALLENGE . $request->URI()->Route(2));

		if (!$challenge->Exists())
			return QuarkDTO::ForStatus(QuarkDTO::STATUS_404_NOT_FOUND);

		return $challenge->Render();
	}
}