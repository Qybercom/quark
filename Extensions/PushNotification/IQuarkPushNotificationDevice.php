<?php
namespace Quark\Extensions\PushNotification;

/**
 * Interface IQuarkPushNotificationDevice
 *
 * @package Quark\Extensions\PushNotification
 */
interface IQuarkPushNotificationDevice {
	/**
	 * @param PushNotificationDevice $device
	 *
	 * @return bool
	 */
	public function PushNotificationDeviceFromDevice(PushNotificationDevice $device);

	/**
	 * @param PushNotificationDevice $device
	 *
	 * @return bool
	 */
	public function PushNotificationDeviceValidate(PushNotificationDevice &$device);
}