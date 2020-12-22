<?php
namespace Quark\Extensions\JOSE\JWA\Providers;

use Quark\Extensions\JOSE\JWT;
use Quark\Extensions\JOSE\JWA\IJOSEJWAAlgorithmProvider;

/**
 * Class OpenSSL
 *
 * @package Quark\Extensions\JOSE\JWA\Providers
 */
class OpenSSL implements IJOSEJWAAlgorithmProvider {
	const NAME = 'OpenSSL';

	const CODE_RS256 = 'RS256';
	const CODE_RS384 = 'RS384';
	const CODE_RS512 = 'RS512';

	/**
	 * @var string[] $_ciphers
	 */
	private static $_ciphers = array(
		self::CODE_RS256 => JWT::CIPHER_SHA256,
		self::CODE_RS384 => JWT::CIPHER_SHA384,
		self::CODE_RS512 => JWT::CIPHER_SHA512
	);

	/**
	 * @param string $code
	 *
	 * @return string
	 */
	public function JOSEJWAAlgorithmCipher ($code) {
		return isset(self::$_ciphers[$code]) ? self::$_ciphers[$code] : null;
	}

	/**
	 * @param string $data
	 * @param string $signature
	 * @param string $key
	 * @param string $algorithm
	 *
	 * @return bool
	 */
	public function JOSEJWAAlgorithmSignatureCheck ($data, $signature, $key, $algorithm) {
		return openssl_verify($data, $signature, $key, $algorithm) === 1;
	}
}