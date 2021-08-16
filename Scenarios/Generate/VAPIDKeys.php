<?php
namespace Quark\Scenarios\Generate;

use Quark\IQuarkTask;

use Quark\Extensions\JOSE\JWK\JWK;
use Quark\Extensions\JOSE\JWK\Providers\EC;
use Quark\Extensions\JOSE\JWT;

/**
 * Class VAPIDKeys
 *
 * https://github.com/web-push-libs/web-push-php/blob/master/src/VAPID.php
 *
 * @package Quark\Scenarios\Generate
 */
class VAPIDKeys implements IQuarkTask {

	/**
	 * @param int $argc
	 * @param array $argv
	 *
	 * @return mixed
	 */
	public function Task ($argc, $argv) {
		$jwk = new JWK();
		$jwk->Type(JWK::TYPE_EC);
		$jwk->Curve(EC::CURVE_P_256);

		$jwt = new JWT();
		$jwk = $jwt->JWKGenerate($jwk);

		print_r($jwk);

		$keyPublic = $jwk->SerializePublicKey();
		$keyPrivate = $jwk->SerializePrivateKey();

		var_dump($keyPublic, $keyPrivate);
	}
}