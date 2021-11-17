<?php
namespace Quark\Extensions\JOSE\Algorithms;

use Quark\Extensions\JOSE\IJOSEAlgorithm;
use Quark\Extensions\JOSE\JOSEKey;
use Quark\QuarkEncryptionKey;

/**
 * Class JOSEAlgorithmRSA
 *
 * https://github.com/firebase/php-jwt/blob/master/src/JWT.php
 *
 * @package Quark\Extensions\JOSE\Algorithms
 */
class JOSEAlgorithmRSA implements IJOSEAlgorithm {
	const TYPE = 'RSA';

	const NAME_RS256 = 'RS256';
	const NAME_RS384 = 'RS384';
	const NAME_RS512 = 'RS512';
	const NAME_PS256 = 'PS256';
	const NAME_PS384 = 'PS384';
	const NAME_PS512 = 'PS512';

	const HASH_SHA256 = 'sha256';
	const HASH_SHA384 = 'sha384';
	const HASH_SHA512 = 'sha512';

	/**
	 * @var string $_hashes
	 */
	private static $_hashes = array(
		self::NAME_RS256 => self::HASH_SHA256,
		self::NAME_RS384 => self::HASH_SHA384,
		self::NAME_RS512 => self::HASH_SHA512,
		self::NAME_PS256 => self::HASH_SHA256,
		self::NAME_PS384 => self::HASH_SHA384,
		self::NAME_PS512 => self::HASH_SHA512
	);

	/**
	 * @param JOSEKey $keyTarget
	 * @param QuarkEncryptionKey $keySource
	 *
	 * @return bool
	 */
	public function JOSEAlgorithmPopulate (JOSEKey &$keyTarget, QuarkEncryptionKey &$keySource) {
		$details = $keySource->Details();
		//$algorithm = $details->Alg

		$keyTarget->Type(self::TYPE);
		$keyTarget->Algorithm();
		$keyTarget->ExponentPublic($details->ExponentPublic());
		$keyTarget->ExponentPrivate($details->ExponentPrivate());
		$keyTarget->FactorFirstPrime($details->FactorFirstPrime());
		$keyTarget->FactorFirstExponent($details->FactorFirstExponent());
		$keyTarget->FactorSecondPrime($details->FactorSecondPrime());
		$keyTarget->FactorSecondExponent($details->FactorSecondExponent());
		$keyTarget->Modulus($details->Modulus());

		return true;
	}
}