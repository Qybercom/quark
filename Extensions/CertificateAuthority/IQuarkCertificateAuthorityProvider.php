<?php
namespace Quark\Extensions\CertificateAuthority;

use Quark\QuarkCertificate;

/**
 * Interface IQuarkCertificateAuthorityProvider
 *
 * @package Quark\Extensions\CertificateAuthority
 */
interface IQuarkCertificateAuthorityProvider {
	/**
	 * @param object $options
	 *
	 * @return mixed
	 */
	public function SSLAuthorityOptions($options);

	/**
	 * @param QuarkCertificate $certificate
	 *
	 * @return QuarkCertificate
	 */
	public function SSLAuthorityCertificateRequest(QuarkCertificate $certificate);

	/**
	 * @param string $csr
	 * @param resource $key
	 * @param string $altName
	 * @param string $passphrase
	 *
	 * @return QuarkCertificate
	 */
	public function SSLAuthorityCertificateRequestRaw($csr, $key, $altName, $passphrase);

	/**
	 * @param QuarkCertificate $certificate
	 *
	 * @return QuarkCertificate
	 */
	public function SSLAuthorityCertificateRenew(QuarkCertificate $certificate);
}