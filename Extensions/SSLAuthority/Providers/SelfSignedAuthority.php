<?php
namespace Quark\Extensions\SSLAuthority\Providers;

use Quark\Quark;
use Quark\QuarkDateInterval;
use Quark\QuarkCertificate;
use Quark\QuarkObject;

use Quark\Extensions\SSLAuthority\IQuarkSSLAuthorityProvider;

/**
 * Class SelfSignedAuthority
 *
 * @package Quark\Extensions\SSLAuthority\Providers
 */
class SelfSignedAuthority implements IQuarkSSLAuthorityProvider {
	/**
	 * @var int $_period = QuarkDateInterval::DAYS_IN_YEAR
	 */
	private $_period = QuarkDateInterval::DAYS_IN_YEAR;

	/**
	 * @var string $_algo = QuarkCertificate::ALGO_SHA512
	 */
	private $_algo = QuarkCertificate::ALGO_SHA512;

	/**
	 * @var int $_length = QuarkCertificate::DEFAULT_BITS
	 */
	private $_length = QuarkCertificate::DEFAULT_BITS;

	/**
	 * @var int $_type = OPENSSL_KEYTYPE_RSA
	 */
	private $_type = OPENSSL_KEYTYPE_RSA;

	/**
	 * @param string $message = ''
	 * @param bool $openssl = true
	 *
	 * @return null
	 */
	private static function _error ($message = '', $openssl = true) {
		Quark::Log('[SSLAuthority.SelfSignedAuthority] ' . $message . ($openssl ? '. OpenSSL error: "' . openssl_error_string() . '".' : ''), Quark::LOG_WARN);
		return null;
	}

	/**
	 * Options:
	 *  - CertificateValidityPeriod int    365    - Certificate validity period interval in days
	 *  - SigningAlgo               string sha512 - Certificate signing algorithm
	 *  - KeyLength                 int    4096   - Certificate private key default length in bits
	 *  - KeyType                   string RSA    - Certificate private key default type
	 *
	 * @param object $options
	 *
	 * @return mixed
	 */
	public function SSLAuthorityOptions ($options) {
		if (isset($options->CertificateValidityPeriod))
			$this->_period = (int)$options->CertificateValidityPeriod;

		if (isset($options->SigningAlgo))
			$this->_algo = $options->SigningAlgo;

		if (isset($options->KeyLength))
			$this->_length = (int)$options->KeyLength;

		if (isset($options->KeyType))
			$this->_type = QuarkObject::ConstValue('OPENSSL_KEYTYPE_' . $options->KeyType);
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
		$config = QuarkCertificate::OpenSSLConfig($this->_algo, $this->_length, $this->_type, $altName);

		$cert = @openssl_csr_sign($csr, null, $key, $this->_period, $config);
		if (!$cert)
			return self::_error('Cannot sign CSR (generate certificate)');

		$x509 = '';
		if (!openssl_x509_export($cert, $x509))
			return self::_error('Cannot export X509');

		$out = '';
		if (!openssl_pkey_export($key, $out, $passphrase, $config))
			return self::_error('Cannot export private key');

		$certificate = new QuarkCertificate();

		$certificate->Passphrase($passphrase);
		$certificate->Content($x509 . $out);

		return $certificate;
	}

	/**
	 * @param QuarkCertificate $certificate
	 *
	 * @return QuarkCertificate
	 */
	public function SSLAuthorityCertificateRenew (QuarkCertificate $certificate) {
		// TODO: Implement SSLCertificateRenew() method.
	}
}