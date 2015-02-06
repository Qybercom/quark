<?php
namespace Quark\Extensions\PushNotification;

/**
 * Interface IPushNotificationProvider
 */
interface IPushNotificationProvider {
	/**
	 * @return string
	 */
	function Type();

	/**
	 * @param $config
	 */
	function Config($config);

	/**
	 * @param Device $device
	 */
	function Device(Device $device);

	/**
	 * @param $payload
	 *
	 * @return mixed
	 */
	function Send($payload);
}