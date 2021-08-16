<?php
namespace Quark\Extensions\PushNotification\Providers\WebPush;

use Quark\Extensions\PushNotification\Device;
use Quark\Extensions\PushNotification\IQuarkPushNotificationDetails;
use Quark\Extensions\PushNotification\IQuarkPushNotificationProvider;

/**
 * Class WebPush
 *
 * @package Quark\Extensions\PushNotification\Providers\WebPush
 */
class WebPush implements IQuarkPushNotificationProvider {

	/**
	 * @return string
	 */
	public function PNPType () {
		// TODO: Implement PNPType() method.
	}

	/**
	 * @param $config
	 */
	public function PNPConfig ($config) {
		// TODO: Implement PNPConfig() method.
	}

	/**
	 * @param string $key
	 * @param $value
	 *
	 * @return mixed
	 */
	public function PNPOption ($key, $value) {
		// TODO: Implement PNPOption() method.
	}

	/**
	 * @param Device $device
	 */
	public function PNPDevice (Device &$device) {
		// TODO: Implement PNPDevice() method.
	}

	/**
	 * @return Device[]
	 */
	public function &PNPDevices () {
		// TODO: Implement PNPDevices() method.
	}

	/**
	 * @param IQuarkPushNotificationDetails $details
	 *
	 * @return mixed
	 */
	public function PNPDetails (IQuarkPushNotificationDetails $details) {
		// TODO: Implement PNPDetails() method.
	}

	/**
	 * @param object|array $payload
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function PNPSend ($payload, $options) {
		// TODO: Implement PNPSend() method.
	}

	/**
	 * @return mixed
	 */
	public function PNPReset () {
		// TODO: Implement PNPReset() method.
	}
}