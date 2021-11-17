<?php
namespace Quark\Extensions\PushNotification\Providers\AppleAPNS;

use Quark\Extensions\PushNotification\IQuarkPushNotificationDevice;
use Quark\Extensions\PushNotification\PushNotificationDevice;

/**
 * Class AppleAPNSDevice
 *
 * @package Quark\Extensions\PushNotification\Providers\AppleAPNS
 */
class AppleAPNSDevice implements IQuarkPushNotificationDevice {
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
		return preg_match('#^[a-f0-9\<\> ]+$#Uis', $device->id) !== false;
	}
}