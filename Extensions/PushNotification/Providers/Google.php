<?php
namespace Quark\Extensions\PushNotification\Providers;

use Quark\Extensions\PushNotification\IPushNotificationProvider;

/**
 * Class Google
 *
 * @package Quark\Extensions\PushNotification\Providers
 */
class Google implements IPushNotificationProvider {
	/**
	 * @return string
	 */
	public function Type () {
		return 'android';
	}
}