<?php
namespace Quark\Extensions\JOSE\Algorithms;

use Quark\QuarkEncryptionKey;

use Quark\Extensions\Quark\EncryptionAlgorithms\EncryptionAlgorithmEC;

use Quark\Extensions\JOSE\IJOSEAlgorithm;
use Quark\Extensions\JOSE\JOSEKey;
use Quark\Extensions\JOSE\JOSEHeader;

/**
 * Class JOSEAlgorithmEC
 *
 * @package Quark\Extensions\JOSE\Algorithms
 */
class JOSEAlgorithmEC implements IJOSEAlgorithm {
	const TYPE = 'EC';

	const CURVE_P_256 = 'P-256';
	const CURVE_P_384 = 'P-384';
	const CURVE_P_521 = 'P-521';

	const ALGORITHM_ES256 = 'ES256';
	const ALGORITHM_ES384 = 'ES384';
	const ALGORITHM_ES512 = 'ES512';

	/**
	 * @var string[] $_curves
	 */
	private static $_curves = array(
		self::CURVE_P_256 => EncryptionAlgorithmEC::OPENSSL_CURVE_PRIME256V1,
		self::CURVE_P_384 => EncryptionAlgorithmEC::OPENSSL_CURVE_SECP384R1,
		self::CURVE_P_521 => EncryptionAlgorithmEC::OPENSSL_CURVE_SECP521R1
	);

	/**
	 * @var string[] $_algorithms
	 */
	private static $_algorithms = array(
		self::CURVE_P_256 => self::ALGORITHM_ES256,
		self::CURVE_P_384 => self::ALGORITHM_ES384,
		self::CURVE_P_521 => self::ALGORITHM_ES512
	);

	/**
	 * @param JOSEKey $keyTarget
	 * @param QuarkEncryptionKey $keySource
	 *
	 * @return bool
	 */
	public function JOSEAlgorithmKeyPopulate (JOSEKey &$keyTarget, QuarkEncryptionKey &$keySource) {
		$details = $keySource->Details();
		$curve = $details->Curve();
		$curves = array_flip(self::$_curves);
		if (!isset($curves[$curve])) return false;

		$keyTarget->Type(self::TYPE);
		$keyTarget->Curve($curves[$curve]);
		$keyTarget->CurveCoordinateX($details->CurveCoordinateX());
		$keyTarget->CurveCoordinateY($details->CurveCoordinateY());
		$keyTarget->Modulus($details->Modulus());

		return true;
	}

	/**
	 * @param JOSEKey $keySource
	 *
	 * @return JOSEHeader
	 */
	public function JOSEAlgorithmHeader (JOSEKey &$keySource) {
		$out = new JOSEHeader();

		$out->Algorithm(self::$_algorithms[$keySource->Curve()]);

		return $out;
	}
}