<?php
namespace Quark\Extensions\PushNotification\Providers\GoogleGCM;

use Quark\Extensions\PushNotification\IQuarkPushNotificationDetails;
use Quark\Extensions\PushNotification\IQuarkPushNotificationDevice;

use Quark\Extensions\PushNotification\PushNotificationDetails;

/**
 * Class GoogleGCMDetails
 *
 * @package Quark\Extensions\PushNotification\Providers\GoogleGCM
 */
class GoogleGCMDetails implements IQuarkPushNotificationDetails {

	/**
	 * @param object|array $payload
	 * @param IQuarkPushNotificationDevice $device = null
	 *
	 * @return mixed
	 */
	public function PushNotificationDetailsData ($payload, IQuarkPushNotificationDevice $device = null) {
		return $payload;
	}

	/**
	 * @param PushNotificationDetails $details
	 *
	 * @return mixed
	 */
	public function PushNotificationDetailsFromDetails (PushNotificationDetails $details) {
		// TODO: Implement PushNotificationDetailsFromDetails() method.
	}
}