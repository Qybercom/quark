<?php
namespace Quark\Extensions\PushNotification\Providers\WebPush;

use Quark\Extensions\JOSE\JWT;

use Quark\Extensions\JOSE\JWK\JWK;
use Quark\Extensions\JOSE\JWK\Providers\EC;

/**
 * Class VoluntaryApplicationServerIdentity
 *
 * @package Quark\Extensions\PushNotification\Providers\WebPush
 */
class VoluntaryApplicationServerIdentity {
	/**
	 * @var JWK $_jwk
	 */
	private $_jwk;

	/**
	 * @param JWK $jwk = null
	 */
	public function __construct (JWK $jwk = null) {
		$this->JWK($jwk);
	}

	/**
	 * @param JWK $jwk = null
	 *
	 * @return JWK
	 */
	public function &JWK (JWK $jwk = null) {
		if (func_num_args() != 0)
			$this->_jwk = $jwk;

		return $this->_jwk;
	}

	/**
	 * @param string $type = JWK::TYPE_EC
	 * @param string $curve = EC::CURVE_P_256
	 *
	 * @return VoluntaryApplicationServerIdentity
	 */
	public static function Generate ($type = JWK::TYPE_EC, $curve = EC::CURVE_P_256) {
		$jwk = new JWK();
		$jwk->Type($type);
		$jwk->Curve($curve);

		$jwt = new JWT();
		$jwk = $jwt->JWKGenerate($jwk);

		return new self($jwk);
	}
}