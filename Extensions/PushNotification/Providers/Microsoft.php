<?php
namespace Quark\Extensions\PushNotification\Providers;

use Quark\Extensions\PushNotification\Device;
use Quark\Extensions\PushNotification\IPushNotificationProvider;

/**
 * Class Microsoft
 *
 * @package Quark\Extensions\PushNotification\Providers
 */
class Microsoft implements IPushNotificationProvider {
	/**
	 * @return string
	 */
	public function Type () {
		return 'windows';
	}

	/**
	 * @return string
	 */
	public function URL () {
		// TODO: Implement URL() method.
	}

	/**
	 * @param mixed $payload
	 *
	 * @return string
	 */
	public function Payload ($payload) {
		// TODO: Implement Payload() method.
	}

	/**
	 * @param array $opt
	 *
	 * @return mixed
	 */
	public function Options ($opt) {
		// TODO: Implement Options() method.
	}
}