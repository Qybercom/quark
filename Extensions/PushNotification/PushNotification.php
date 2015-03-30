<?php
namespace Quark\Extensions\PushNotification;

use Quark\IQuarkExtension;

use Quark\Quark;

/**
 * Class PushNotification
 *
 * @package Quark\Extensions\PushNotification
 */
class PushNotification implements IQuarkExtension {
	/**
	 * @var PushNotificationConfig $_config
	 */
	private $_config;

	/**
	 * @var $_payload
	 */
	private $_payload = array();

	/**
	 * @var Device[] $_devices
	 */
	private $_devices = array();

	/**
	 * @var array $_options
	 */
	private $_options = array();

	/**
	 * @param string $config
	 * @param mixed $payload
	 */
	public function __construct ($config, $payload = []) {
		$this->_config = Quark::Config()->Extension($config);
		$this->_payload = $payload;
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
	 * @param IQuarkPushNotificationProvider $provider
	 * @param array                          $options
	 */
	public function Options (IQuarkPushNotificationProvider $provider, $options = []) {
		$this->_options[$provider->Type()] = $options;
	}

	/**
	 * @return bool
	 */
	public function Send () {
		$ok = true;
		$providers = $this->_config->Providers();

		foreach ($providers as $provider) {
			foreach ($this->_devices as $device)
				if ($device->type == $provider->Type())
					$provider->Device($device);

			$ok &= $provider->Send($this->_payload, isset($this->_options[$provider->Type()])
				? $this->_options[$provider->Type()]
				: array());
			$provider->Reset();
		}

		return $ok;
	}
}