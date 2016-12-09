<?php
namespace Quark\Extensions\PushNotification;

/**
 * Interface IQuarkPushNotificationDetails
 *
 * @package Quark\Extensions\PushNotification
 */
interface IQuarkPushNotificationDetails {
	/**
	 * @return string
	 */
	public function PNProviderType();

	/**
	 * @param object|array$payload
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function PNDetails($payload, $options);
}