<?php
namespace Quark\Extensions\SSLAuthority\Providers;

use Quark\Quark;
use Quark\QuarkArchException;
use Quark\QuarkCertificate;
use Quark\QuarkDTO;
use Quark\QuarkFile;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
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
			return self::_error('DomainChallenge: Dmain must not be empty', false);

		$response = $this->API('/acme/new-reg', array(
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
			$certificate->KeyPrivate(),
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