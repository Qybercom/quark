<?php
namespace Quark\Extensions\PushNotification;

use Quark\IQuarkExtension;

use Quark\Quark;
use Quark\QuarkDate;

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
	 * @var object|array $_payload
	 */
	private $_payload = array();

	/**
	 * @var Device[] $_devices
	 */
	private $_devices = array();

	/**
	 * @var array $_details
	 */
	private $_details = array();

	/**
	 * @var array $_options
	 */
	private $_options = array();

	/**
	 * @param string $config
	 * @param object|array $payload
	 */
	public function __construct ($config, $payload = []) {
		$this->_config = Quark::Config()->Extension($config);
		$this->_payload = $payload;
	}

	/**
	 * @param object|array $payload
	 *
	 * @return object|array
	 */
	public function Payload ($payload = []) {
		if (func_num_args() == 1)
			$this->_payload = $payload;

		return $this->_payload;
	}

	/**
	 * @param Device $device = null
	 *
	 * @return PushNotification
	 */
	public function Device (Device $device = null) {
		if ($device != null)
			$this->_devices[] = $device;

		return $this;
	}

	/**
	 * @return Device[]
	 */
	public function &Devices () {
		return $this->_devices;
	}

	/**
	 * @param IQuarkPushNotificationDetails $details = null
	 *
	 * @return PushNotification
	 */
	public function Details (IQuarkPushNotificationDetails $details = null) {
		if ($details != null)
			$this->_details[$details->PNProviderType()] = $details;

		return $this;
	}

	/**
	 * @param IQuarkPushNotificationProvider $provider
	 * @param array                          $options
	 */
	public function Options (IQuarkPushNotificationProvider $provider, $options = []) {
		$this->_options[$provider->PNPType()] = $options;
	}

	/**
	 * @param QuarkDate|string $ageEdge = ''
	 *
	 * @return bool
	 */
	public function Send ($ageEdge = '') {
		if (!$this->_config) {
			Quark::Log('PushNotification does not have a valid config', Quark::LOG_WARN);
			return false;
		}

		$ok = true;
		$providers = $this->_config->Providers();

		foreach ($providers as $provider) {
			$devices = 0;

			foreach ($this->_devices as $device) {
				if (func_num_args() != 0 && ($device->date == null || $device->date->Earlier(QuarkDate::From($ageEdge)))) continue;

				if ($device && $device->id != '' && $provider->PNPDevice($device))
					$devices++;
			}

			if ($devices == 0) continue;

			if (isset($this->_details[$provider->PNPType()]))
				$provider->PNPDetails($this->_details[$provider->PNPType()]);

			$ok &= (bool)$provider->PNPSend($this->_payload, isset($this->_options[$provider->PNPType()])
				? $this->_options[$provider->PNPType()]
				: array());

			$provider->PNPReset();
		}

		return $ok;
	}
}