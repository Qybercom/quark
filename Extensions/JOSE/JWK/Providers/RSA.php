<?php
namespace Quark\Extensions\JOSE\JWK\Providers;

use Quark\Extensions\JOSE\JWK\IJOSEJWKAlgorithmProvider;
use Quark\Extensions\JOSE\JWK\JWK;
use Quark\Extensions\JOSE\JWT;

/**
 * Class RSA
 *
 * @package Quark\Extensions\JOSE\JWK\Providers
 */
class RSA implements IJOSEJWKAlgorithmProvider {
	const OID = '300d06092a864886f70d0101010500';

	/**
	 * @param JWK $jwk
	 *
	 * @return JWK
	 */
	public function JOSEJWKAlgorithmProviderRetrieve (JWK $jwk) {
		$data = $jwk->Data();

		if (!isset($data->d) && isset($data->n) && isset($data->e)) {
			$key = self::_key(self::_component($data->n) . self::_component($data->e));
			$key = chr(0) . $key;
			$key = chr(3) . self::_length($key) . $key;
			$key = self::_key(pack('H*', self::OID) . $key);

			$out = openssl_pkey_get_details(openssl_pkey_get_public(
				'-----BEGIN PUBLIC KEY-----' . "\r\n" .
				chunk_split(base64_encode($key), 64) .
				'-----END PUBLIC KEY-----'
			));

			if (isset($out['key']))
				$jwk->Content($out['key']);
		}

		return $jwk;
	}

	/**
	 * @param JWK $jwk
	 *
	 * @return JWK
	 */
	public function JOSEJWKAlgorithmProviderGenerate (JWK $jwk) {
		// TODO: Implement JOSEJWKAlgorithmProviderGenerate() method.
	}

	/**
	 * @param string $data = ''
	 *
	 * @return string
	 */
	private static function _key ($data = '') {
		return pack('Ca*a*', 48, self::_length($data), $data);
	}

	/**
	 * @param string $data = ''
	 *
	 * @return string
	 */
	private static function _component ($data = '') {
		$raw = JWT::Base64Decode($data);

		return pack('Ca*a*', 2, self::_length($raw), $raw);
	}

    /**
     * @param string $data = ''
	 *
     * @return string
     */
    private static function _length ($data = '') {
		$length = strlen($data);
        if ($length <= 0x7F) return chr($length);

        $temp = ltrim(pack('N', $length), chr(0));

        return pack('Ca*', 0x80 | strlen($temp), $temp);
    }
}