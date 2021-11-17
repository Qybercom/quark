<?php
namespace Quark\Extensions\PushNotification;

/**
 * Interface IQuarkPushNotificationDetails
 *
 * @package Quark\Extensions\PushNotification
 */
interface IQuarkPushNotificationDetails {
	/**
	 * @param object|array $payload
	 * @param IQuarkPushNotificationDevice $device = null
	 *
	 * @return mixed
	 */
	public function PushNotificationDetailsData($payload, IQuarkPushNotificationDevice $device = null);

	/**
	 * @param PushNotificationDetails $details
	 *
	 * @return mixed
	 */
	public function PushNotificationDetailsFromDetails(PushNotificationDetails $details);
}