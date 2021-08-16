<?php
namespace Quark\Extensions\JOSE\JWK\Providers;

use Quark\QuarkCipherKeyPair;

use Quark\Extensions\JOSE\JWK\IJOSEJWKAlgorithmProvider;
use Quark\Extensions\JOSE\JWK\JWK;
use Quark\Extensions\JOSE\JWT;

/**
 * Class EC
 *
 * https://github.com/web-token/jwt-core/blob/6d81e63599ea0e58027e58938331138fb82b1d0b/Util/ECKey.php#L101
 *
 * @package Quark\Extensions\JOSE\JWK\Providers
 */
class EC implements IJOSEJWKAlgorithmProvider {
	const CURVE_P_256 = 'P-256';
	const CURVE_P_384 = 'P-384';
	const CURVE_P_512 = 'P-512';

	/**
	 * @var string[] $_algorithm
	 */
	private static $_algorithm = array(
		self::CURVE_P_256 => 'prime256v1',
		self::CURVE_P_384 => 'secp384r1',
		self::CURVE_P_512 => 'secp521r1'
	);

	/**
	 * @var int[] $_curveSize
	 */
	private static $_curveSize = array(
		self::CURVE_P_256 => 256,
		self::CURVE_P_384 => 384,
		self::CURVE_P_512 => 512
	);

	/**
	 * @param JWK $jwk
	 *
	 * @return JWK
	 */
	public function JOSEJWKAlgorithmProviderRetrieve (JWK $jwk) {
		// TODO: Implement JOSEJWKAlgorithmProviderRetrieve() method.
	}

	/**
	 * @param JWK $jwk
	 *
	 * @return JWK
	 */
	public function JOSEJWKAlgorithmProviderGenerate (JWK $jwk) {
		$keys = QuarkCipherKeyPair::GenerateNewByParams(array(
			'curve_name' => self::$_algorithm[$jwk->Curve()],
			'private_key_type' => OPENSSL_KEYTYPE_EC
		));

		$details = $keys->PrivateKeyDetails();

		$jwk->ExponentPrivate(self::_componentEncode($details, 'd', $jwk->Curve()));
		$jwk->CurveCoordinateX(self::_componentEncode($details, 'x', $jwk->Curve()));
		$jwk->CurveCoordinateY(self::_componentEncode($details, 'y', $jwk->Curve()));

		return $jwk;
	}

	/**
	 * @param array $source = []
	 * @param string $component = ''
	 * @param string $algorithm = ''
	 *
	 * @return string
	 */
	private static function _componentEncode ($source = [], $component = '', $algorithm = '') {
		return JWT::Base64Encode(str_pad($source['ec'][$component], ceil(self::$_curveSize[$algorithm] / 8), "\0", STR_PAD_LEFT));
	}
}