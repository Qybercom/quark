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
	 * @return string
	 */
	function URL();

	/**
	 * Config Push Notification Provider
	 */
	function Config();

	/**
	 * @return \Quark\QuarkClientDTO
	 */
	function Request();

	/**
	 * @return \Quark\QuarkClientDTO
	 */
	function Response();

	/**
	 * @param array $opt
	 */
	function Options($opt);

	/**
	 * @param Device $device
	 */
	function Device($device);
}