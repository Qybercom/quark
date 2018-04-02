<?php
namespace Quark\Extensions\CertificateAuthority;

use Quark\IQuarkExtension;

use Quark\Quark;
use Quark\QuarkCertificate;
use Quark\QuarkCertificateSAN;

/**
 * Class CertificateAuthority
 *
 * @package Quark\Extensions\CertificateAuthority
 */
class CertificateAuthority implements IQuarkExtension {
	/**
	 * @var CertificateAuthorityConfig $_config
	 */
	private $_config;

	/**
	 * @param string $config
	 */
	public function __construct ($config) {
		$this->_config = Quark::Config()->Extension($config);
	}

	/**
	 * @param QuarkCertificate $certificate = null
	 *
	 * @return QuarkCertificate
	 */
	public function CertificateRequest (QuarkCertificate $certificate = null) {
		if ($certificate == null) return null;

		$out = $this->_config->Provider()->SSLAuthorityCertificateRequest($certificate);

		return $out;
	}

	/**
	 * @param string $csr
	 * @param resource $key
	 * @param string $altName
	 * @param string $passphrase = null
	 *
	 * @return QuarkCertificate
	 */
	public function CertificateRequestRaw ($csr, $key, $altName, $passphrase = null) {
		$out = $this->_config->Provider()->SSLAuthorityCertificateRequestRaw($csr, $key, $altName, $passphrase);

		return $out;
	}

	/**
	 * @param QuarkCertificate $certificate = null
	 *
	 * @return QuarkCertificate
	 */
	public function CertificateRenew (QuarkCertificate $certificate = null) {
		if ($certificate == null) return null;

		$out = $this->_config->Provider()->SSLAuthorityCertificateRenew($certificate);

		return $out;
	}

	/**
	 * @param string[] $domains = []
	 * @param string $passphrase = null
	 *
	 * @return QuarkCertificate
	 */
	public function SignDomains ($domains = [], $passphrase = null) {
		if (sizeof($domains) == 0) return null;

		$certificate = QuarkCertificate::ForDomainCSR($domains[0], $passphrase);
		$domains = array_slice($domains, 1);

		foreach ($domains as $i => &$domain)
			$certificate->AltName(new QuarkCertificateSAN($domain));

		unset($i, $domain);

		return $this->CertificateRequest($certificate);
	}
}