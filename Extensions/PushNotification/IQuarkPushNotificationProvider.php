<?php
namespace Quark\Extensions\PushNotification;

/**
 * Interface IQuarkPushNotificationProvider
 */
interface IQuarkPushNotificationProvider {
	/**
	 * @return string
	 */
	public function PushNotificationProviderType();

	/**
	 * @return string[]
	 */
	public function PushNotificationProviderProperties();

	/**
	 * @param string $config
	 *
	 * @return mixed
	 */
	public function PushNotificationProviderInit($config);

	/**
	 * @return IQuarkPushNotificationDetails
	 */
	public function PushNotificationProviderDetails();

	/**
	 * @return IQuarkPushNotificationDevice
	 */
	public function PushNotificationProviderDevice();

	/**
	 * @param PushNotificationDevice $device
	 *
	 * @return mixed
	 */
	public function PushNotificationProviderDeviceAdd(PushNotificationDevice &$device);

	/**
	 * @param IQuarkPushNotificationDetails $details
	 * @param object|array $payload
	 *
	 * @return PushNotificationResult
	 */
	public function PushNotificationProviderSend(IQuarkPushNotificationDetails &$details, $payload);

	/**
	 * @return mixed
	 */
	public function PushNotificationProviderReset();
}