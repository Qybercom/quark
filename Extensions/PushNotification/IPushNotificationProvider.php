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
	 * @return string
	 */
	function URL();

	/**
	 * @param $payload
	 * @return \Quark\QuarkClientDTO
	 */
	function Request($payload);

	/**
	 * @return \Quark\QuarkClientDTO
	 */
	function Response();
}