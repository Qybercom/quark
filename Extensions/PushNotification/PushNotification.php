<?php
namespace Quark\Extensions\PushNotification;

use Quark\IQuarkExtension;

/**
 * Class PushNotification
 *
 * @package Quark\Extensions\PushNotification
 */
class PushNotification implements IQuarkExtension {
	/**
	 * @var IPushNotificationProvider[] $_providers
	 */
	private static $_providers = array();

	/**
	 * @var $_payload
	 */
	private $_payload = array();

	/**
	 * @var Device[] $_devices
	 */
	private $_devices = array();

	/**
	 * @param mixed $payload
	 */
	public function __construct ($payload = []) {
		$this->_payload = $payload;
	}

	/**
	 * @param IPushNotificationProvider $provider
	 * @param $config
	 */
	public function Provider (IPushNotificationProvider $provider, $config = []) {
		$provider->Config($config);
		self::$_providers[] = $provider;
	}

	/**
	 * @param array $payload
	 *
	 * @return array
	 */
	public function Payload ($payload = []) {
		if (func_num_args() == 1)
			$this->_payload = $payload;

		return $this->_payload;
	}

	/**
	 * @param Device $device
	 */
	public function Device (Device $device) {
		$this->_devices[] = $device;
	}

	/**
	 * @return bool
	 */
	public function Send () {
		$ok = true;

		foreach (self::$_providers as $provider) {
			/**
			 * @var $provider IPushNotificationProvider
			 */
			print_r($provider);
			foreach ($this->_devices as $device)
				if ($device->type == $provider->Type())
					$provider->Device($device);

			$ok &= $provider->Send($this->_payload);
		}

		return $ok;
	}
}