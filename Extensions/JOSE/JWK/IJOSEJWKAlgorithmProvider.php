<?php
namespace Quark\Extensions\JOSE\JWK;

/**
 * Interface IJOSEJWKAlgorithmProvider
 *
 * @package Quark\Extensions\JOSE\JWK
 */
interface IJOSEJWKAlgorithmProvider {
	/**
	 * @param JWK $jwk
	 *
	 * @return JWK
	 */
	public function JOSEJWKAlgorithmProviderRetrieve(JWK $jwk);

	/**
	 * @param JWK $jwk
	 *
	 * @return JWK
	 */
	public function JOSEJWKAlgorithmProviderGenerate(JWK $jwk);
}