<?php
namespace Quark\Extensions\PushNotification\Providers;

use Quark\Extensions\PushNotification\IPushNotificationProvider;

/**
 * Class Apple
 *
 * @package Quark\Extensions\PushNotification\Providers
 */
class Apple implements IPushNotificationProvider {
	/**
	 * @return string
	 */
	public function Type () {
		return 'ios';
	}
}