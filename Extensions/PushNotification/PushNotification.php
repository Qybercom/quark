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
	 * @var IQuarkPushNotificationDetails[] $_queue = []
	 */
	private $_queue = array();

	/**
	 * @var object|array $_payload
	 */
	private $_payload = array();

	/**
	 * @var PushNotificationDetails $_details = null
	 */
	private $_details = null;

	/**
	 * @var PushNotificationDevice[] $_devices
	 */
	private $_devices = array();

	/**
	 * @param object|array $payload = []
	 */
	public function __construct ($payload = []) {
		$this->Payload($payload);
	}

	/**
	 * @param object|array $payload = []
	 *
	 * @return object|array
	 */
	public function Payload ($payload = []) {
		if (func_num_args() != 0)
			$this->_payload = $payload;

		return $this->_payload;
	}

	/**
	 * @param PushNotificationDetails $details = null
	 *
	 * @return PushNotificationDetails
	 */
	public function Details (PushNotificationDetails $details = null) {
		if (func_num_args() != 0)
			$this->_details = $details;

		return $this->_details;
	}

	/**
	 * @param PushNotificationDevice $device = null
	 *
	 * @return PushNotification
	 */
	public function Device (PushNotificationDevice $device = null) {
		if ($device != null)
			$this->_devices[] = $device;

		return $this;
	}

	/**
	 * @param string $type = ''
	 * @param string $id = ''
	 * @param QuarkDate|string $date = ''
	 *
	 * @return PushNotification
	 */
	public function DeviceByValues ($type = '', $id = '', $date = '') {
		return $this->Device(new PushNotificationDevice($type, $id, $date));
	}

	/**
	 * @param object|array $device = null
	 * @param QuarkDate|string $date = ''
	 *
	 * @return PushNotification
	 */
	public function DeviceFromObject ($device = null, $date = '') {
		return $this->Device(PushNotificationDevice::FromObject($device, $date));
	}

	/**
	 * @return PushNotificationDevice[]
	 */
	public function &Devices () {
		return $this->_devices;
	}

	/**
	 * @param string $config = ''
	 * @param IQuarkPushNotificationDetails $details = null
	 *
	 * @return PushNotification
	 */
	public function Queue ($config = '', IQuarkPushNotificationDetails $details = null) {
		if (func_num_args() != 0)
			$this->_queue[$config] = $details;

		return $this;
	}

	/**
	 * @param QuarkDate $ageEdge = null
	 * @param bool $autoReset = true
	 *
	 * @return PushNotificationResult[]
	 */
	public function Send (QuarkDate $ageEdge = null, $autoReset = true) {
		$out = array();

		$config = null;
		$provider = null;
		$i = 0;
		$device = null;
		$detailsBuffer = null;

		foreach ($this->_queue as $configKey => &$details) {
			$config = Quark::Config()->Extension($configKey);

			if (!($config instanceof PushNotificationConfig)) {
				Quark::Log('[PushNotification] Config key "' . $configKey . '" is not an IQuarkPushNotificationConfig', Quark::LOG_WARN);
				continue;
			}

			$provider = $config->Provider();

			foreach ($this->_devices as $i => &$device)
				if ($device->Valid($provider, $ageEdge))
					$provider->PushNotificationProviderDeviceAdd($device);

			$detailsBuffer = $details == null ? $provider->PushNotificationProviderDetails() : clone $details;
			$detailsBuffer->PushNotificationDetailsFromDetails($this->_details);

			$out[$configKey] = $provider->PushNotificationProviderSend($detailsBuffer, $this->_payload);

			if ($autoReset)
				$provider->PushNotificationProviderReset();
		}

		unset($i, $device, $details, $detailsBuffer, $provider, $config);

		return $out;
	}

	/**
	 * @return PushNotification
	 */
	public function Reset () {
		foreach ($this->_queue as $configKey => &$details) {
			$config = Quark::Config()->Extension($configKey);

			if (!($config instanceof PushNotificationConfig)) {
				Quark::Log('[PushNotification] Config key "' . $configKey . '" is not an IQuarkPushNotificationConfig', Quark::LOG_WARN);
				continue;
			}

			$provider = $config->Provider();
			$provider->PushNotificationProviderReset();
		}

		unset($configKey, $details, $config, $provider);

		return $this;
	}

	/**
	 * @param string $config = ''
	 * @param IQuarkPushNotificationDetails $details = null
	 * @param object|array $payload = []
	 *
	 * @return PushNotification
	 */
	public static function Enqueue ($config = '', IQuarkPushNotificationDetails $details = null, $payload = []) {
		$out = new self($payload);
		$out->Queue($config, $details);

		return $out;
	}

	/**
	 * @param string $config = ''
	 * @param PushNotificationDevice $device = null
	 * @param PushNotificationDetails $details = null
	 * @param IQuarkPushNotificationDetails $detailsProvider = null
	 * @param object|array $payload
	 *
	 * @return PushNotificationResult[]
	 */
	public static function SendToOneDevice ($config = '', PushNotificationDevice $device = null, PushNotificationDetails $details = null, IQuarkPushNotificationDetails $detailsProvider = null, $payload = []) {
		$out = self::Enqueue($config, $detailsProvider, $payload)->Device($device);
		$out->Details($details);

		return $out->Send();
	}
}