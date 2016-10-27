<?php
namespace Quark\Extensions\PushNotification\Providers;

use Quark\Extensions\PushNotification\IQuarkPushNotificationProvider;

use Quark\Extensions\PushNotification\Device;

/**
 * Class GoogleFCM
 *
 * @package Quark\Extensions\PushNotification\Providers
 */
class GoogleFCM implements IQuarkPushNotificationProvider {
	const TYPE = 'fcm';

	/**
	 * @var Device[] $_devices = []
	 */
	private $_devices = array();

	/**
	 * @var string $_key = ''
	 */
	private $_key = '';

	/**
	 * @return string
	 */
	public function PNPType () {
		return self::TYPE;
	}

	/**
	 * @param $config
	 */
	public function PNPConfig ($config) {
		if (is_string($config))
			$this->_key = $config;
	}

	/**
	 * @param string $key
	 * @param $value
	 *
	 * @return void
	 */
	public function PNPOption ($key, $value) {
		if (is_string($value))
			$this->_key = $value;
	}

	/**
	 * @param Device $device
	 */
	public function PNPDevice (Device $device) {
		$this->_devices[] = $device->id;
	}

	/**
	 * @return Device[]
	 */
	public function PNPDevices () {
		return $this->_devices;
	}

	/**
	 * @param object|array $payload
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function PNPSend ($payload, $options = []) {
		// TODO: Implement PNPSend() method.
	}

	/**
	 * @return void
	 */
	public function PNPReset () {
		$this->_devices = array();
	}
}