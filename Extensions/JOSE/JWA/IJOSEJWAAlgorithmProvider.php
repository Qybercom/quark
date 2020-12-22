<?php
namespace Quark\Extensions\JOSE\JWA;

/**
 * Interface IJOSEJWAAlgorithmProvider
 *
 * @package Quark\Extensions\JOSE\JWA
 */
interface IJOSEJWAAlgorithmProvider {
	/**
	 * @param string $code
	 *
	 * @return string
	 */
	public function JOSEJWAAlgorithmCipher($code);

	/**
	 * @param string $data
	 * @param string $signature
	 * @param string $key
	 * @param string $algorithm
	 *
	 * @return bool
	 */
	public function JOSEJWAAlgorithmSignatureCheck($data, $signature, $key, $algorithm);
}