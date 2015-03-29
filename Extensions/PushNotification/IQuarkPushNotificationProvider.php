<?php
namespace Quark\Extensions\PushNotification;

/**
 * Interface IQuarkPushNotificationProvider
 */
interface IQuarkPushNotificationProvider {
	/**
	 * @return string
	 */
	public function Type();

	/**
	 * @param $config
	 */
	public function Config($config);

	/**
	 * @param Device $device
	 */
	public function Device(Device $device);

	/**
	 * @param $payload
	 *
	 * @return mixed
	 */
	public function Send($payload);

	/**
	 * @return mixed
	 */
	public function Reset();
}