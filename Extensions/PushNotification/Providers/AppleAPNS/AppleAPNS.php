<?php
namespace Quark\Extensions\PushNotification\Providers\AppleAPNS;

use Quark\Quark;
use Quark\QuarkCertificate;
use Quark\QuarkClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkModel;
use Quark\QuarkTCPNetworkTransport;

use Quark\Extensions\PushNotification\IQuarkPushNotificationProvider;
use Quark\Extensions\PushNotification\IQuarkPushNotificationDetails;

use Quark\Extensions\PushNotification\Device;

/**
 * Class AppleAPNS
 *
 * @package Quark\Extensions\PushNotification\Providers\AppleAPNS
 */
class AppleAPNS extends QuarkJSONIOProcessor implements IQuarkPushNotificationProvider {
	const TYPE = 'ios';

	const OPTION_CERTIFICATE = 'certificate';

	const OPTION_PRODUCTION = 'ssl://gateway.push.apple.com:2195';
	const OPTION_SANDBOX = 'ssl://gateway.sandbox.push.apple.com:2195';

	const INI_CERTIFICATE_LOCATION = 'ios.Certificate.Location';
	const INI_CERTIFICATE_PASSPHRASE = 'ios.Certificate.Passphrase';
	const INI_SANDBOX = 'ios.Sandbox';

	/**
	 * @var Device[] $_devices = []
	 */
	private $_devices = array();

	/**
	 * @var IQuarkPushNotificationDetails|AppleAPNSDetails $_details
	 */
	private $_details;

	/**
	 * @var QuarkCertificate $_certificate
	 */
	private $_certificate;

	/**
	 * @var string $_host = self::OPTION_PRODUCTION
	 */
	private $_host = self::OPTION_PRODUCTION;

	/**
	 * @var array $_payload = []
	 */
	private $_payload = array();

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
		if (isset($config[self::OPTION_CERTIFICATE]) && $config[self::OPTION_CERTIFICATE] instanceof QuarkCertificate)
			$this->_certificate = $config[self::OPTION_CERTIFICATE];

		if (isset($config[self::OPTION_SANDBOX]) && $config[self::OPTION_SANDBOX] == true)
			$this->_host = self::OPTION_SANDBOX;
	}

	/**
	 * @param string $key
	 * @param $value
	 *
	 * @return void
	 */
	public function PNPOption ($key, $value) {
		switch ($key) {
			case self::INI_CERTIFICATE_LOCATION:
				$this->_certificate = new QuarkCertificate($value);
				break;

			case self::INI_CERTIFICATE_PASSPHRASE:
				$this->_certificate->Passphrase($value);
				break;

			case self::INI_SANDBOX:
				if ($value == '1')
					$this->_host = self::OPTION_SANDBOX;
				break;

			default: break;
		}
	}

	/**
	 * @param Device &$device
	 *
	 * @return bool
	 */
	public function PNPDevice (Device &$device) {
		if ($device->type != self::TYPE) return false;

		if (!preg_match('#^[a-f0-9\<\> ]+$#Uis', $device->id)) {
			Quark::Log('[AppleAPNS] Invalid device id "' . $device->id . '"', Quark::LOG_WARN);
			return false;
		}

		$this->_devices[] = $device;

		return true;
	}

	/**
	 * @return Device[]
	 */
	public function &PNPDevices () {
		return $this->_devices;
	}

	/**
	 * @param IQuarkPushNotificationDetails $details
	 *
	 * @return void
	 */
	public function PNPDetails (IQuarkPushNotificationDetails $details) {
		$this->_details = $details;
	}

	/**
	 * @param object|array $payload
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function PNPSend ($payload, $options) {
		if ($this->_certificate == null) {
			Quark::Log('[AppleAPNS] Certificate was not specified or given path for ios.Certificate.Location in "ini" was not resolved', Quark::LOG_WARN);
			return false;
		}

		$data = $payload;

		if (is_scalar($payload)) {
			$this->_details->Alert($payload);
			$data = array();
		}

		$this->_payload = array(
			'aps' => $this->_details->PNDetails($payload, $options),
			'data' => $data
		);

		$devices = array();
		$back = array();

		foreach ($this->_devices as $device) {
			if ($device->date == null) {
				$back[] = $device;
				continue;
			}

			$devices[] = $device;
		}

		usort($devices, function ($a, $b) {
			$date = $b->date instanceof QuarkModel ? $b->date->Model() : $b->date;

			return $a->date->Earlier($date)
				? 1
				: ($a->date->Later($date)
					? -1
					: 0
				);
		});

		$devices = array_merge($devices, $back);

		$client = new QuarkClient($this->_host, new QuarkTCPNetworkTransport(), $this->_certificate, 60, false);

		$client->On(QuarkClient::EVENT_CONNECT, function (QuarkClient $client) use ($devices, $options) {
			foreach ($devices as $device)
				$client->Send($this->_msg($device));
		});

		$client->On(QuarkClient::EVENT_ERROR_CONNECT, function ($error) {
			Quark::Log($error, Quark::LOG_WARN);
		});

		return $client->Connect();
	}

	/**
	 * @return void
	 */
	public function PNPReset () {
		$this->_devices = array();
	}

	/**
	 * @param Device $device
	 *
	 * @return string
	 */
	private function _msg (Device $device) {
		$payload = $this->Encode($this->_payload);

		return chr(0) . pack('n', 32) . pack('H*', str_replace('<', '', str_replace('>', '', str_replace(' ', '', $device->id)))) . pack('n', strlen($payload)) . $payload;
	}
}