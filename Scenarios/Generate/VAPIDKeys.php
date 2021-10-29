<?php
namespace Quark\Scenarios\Generate;

use Quark\IQuarkTask;

use Quark\Extensions\PushNotification\Providers\WebPush\VoluntaryApplicationServerIdentity;

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
		$vapid = VoluntaryApplicationServerIdentity::Generate();
		$jwk = $vapid->JWK();

		print_r($jwk);

		$keyPublic = $jwk->SerializePublicKey();
		$keyPrivate = $jwk->SerializePrivateKey();

		var_dump($keyPublic, $keyPrivate);
	}
}