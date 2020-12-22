<?php
namespace Quark\Extensions\JOSE\JWK;

/**
 * Interface IJOSEJWKProvider
 *
 * @package Quark\Extensions\JOSE\JWK
 */
interface IJOSEJWKProvider {
	/**
	 * @param object $data
	 *
	 * @return JWK
	 */
	public function JOSEJWKProviderKeyExtract($data);
}