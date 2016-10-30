<?php
namespace Quark\Extensions\PushNotification;

/**
 * Interface IQuarkPushNotificationProvider
 */
interface IQuarkPushNotificationProvider {
	/**
	 * @return string
	 */
	public function PNPType();

	/**
	 * @param $config
	 */
	public function PNPConfig($config);

	/**
	 * @param string $key
	 * @param $value
	 *
	 * @return mixed
	 */
	public function PNPOption($key, $value);

	/**
	 * @param Device $device
	 */
	public function PNPDevice(Device &$device);

	/**
	 * @return Device[]
	 */
	public function &PNPDevices();

	/**
	 * @param object|array $payload
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function PNPSend($payload, $options = []);

	/**
	 * @return mixed
	 */
	public function PNPReset();
}