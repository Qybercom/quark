<?php
namespace Quark\Extensions\JOSE\JWA\Providers;

use Quark\Extensions\JOSE\JWT;
use Quark\Extensions\JOSE\JWA\IJOSEJWAAlgorithmProvider;

/**
 * Class HashHMAC
 *
 * @package Quark\Extensions\JOSE\JWA\Providers
 */
class HashHMAC implements IJOSEJWAAlgorithmProvider {
	const NAME = 'HashHMAC';

	const CODE_HS256 = 'HS256';
	const CODE_HS384 = 'HS384';
	const CODE_HS512 = 'HS512';

	/**
	 * @var string[] $_ciphers
	 */
	private static $_ciphers = array(
		self::CODE_HS256 => JWT::CIPHER_SHA256,
		self::CODE_HS384 => JWT::CIPHER_SHA384,
		self::CODE_HS512 => JWT::CIPHER_SHA512
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
		$hash = hash_hmac($algorithm, $data, $key, true);

		if (function_exists('hash_equals'))
			return hash_equals($signature, $hash);

		$len = min(strlen($signature), strlen($hash));
		$i = 0;
		$status = 0;

		while ($i < $len) {
			$status |= (ord($signature[$i]) ^ ord($hash[$i]));

			$i++;
		}

		$status |= strlen($signature) ^ strlen($hash);

		return $status === 0;
	}
}