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
	 * @param mixed $payload
	 *
	 * @return string
	 */
	function Payload($payload);

	/**
	 * @param array $opt
	 *
	 * @return mixed
	 */
	function Options($opt);
}