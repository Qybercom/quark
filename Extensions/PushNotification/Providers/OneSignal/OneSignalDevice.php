<?php
namespace Quark\Extensions\PushNotification\Providers\OneSignal;

use Quark\Extensions\PushNotification\IQuarkPushNotificationDevice;
use Quark\Extensions\PushNotification\PushNotificationDevice;

/**
 * Class OneSignalDevice
 *
 * @package Quark\Extensions\PushNotification\Providers\OneSignal
 */
class OneSignalDevice implements IQuarkPushNotificationDevice {
	/**
	 * @param PushNotificationDevice $device
	 *
	 * @return bool
	 */
	public function PushNotificationDeviceFromDevice (PushNotificationDevice $device) {
		// TODO: Implement PushNotificationDeviceFromDevice() method.
	}

	/**
	 * @param PushNotificationDevice $device
	 *
	 * @return bool
	 */
	public function PushNotificationDeviceValidate (PushNotificationDevice &$device) {
		return true;
	}
}