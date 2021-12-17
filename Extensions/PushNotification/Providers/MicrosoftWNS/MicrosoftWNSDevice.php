<?php
namespace Quark\Extensions\PushNotification\Providers\MicrosoftWNS;

use Quark\QuarkField;

use Quark\Extensions\PushNotification\IQuarkPushNotificationDevice;
use Quark\Extensions\PushNotification\PushNotificationDevice;

/**
 * Class MicrosoftWNSDevice
 *
 * @package Quark\Extensions\PushNotification\Providers\MicrosoftWNS
 */
class MicrosoftWNSDevice implements IQuarkPushNotificationDevice {
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
		return QuarkField::URI($device->id);
	}

	/**
	 * @param PushNotificationDevice $device
	 *
	 * @return mixed
	 */
	public function PushNotificationDeviceCriteriaSQL (PushNotificationDevice &$device) {
		// TODO: Implement PushNotificationDeviceCriteriaSQL() method.
	}

	/**
	 * @param PushNotificationDevice $device
	 *
	 * @return bool
	 */
	public function PushNotificationDeviceUpdateNeed (PushNotificationDevice &$device) {
		// TODO: Implement PushNotificationDeviceUpdateNeed() method.
	}
}