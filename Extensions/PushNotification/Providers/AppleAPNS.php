<?php
namespace Quark\Extensions\PushNotification\Providers;

use Quark\Quark;
use Quark\QuarkCertificate;
use Quark\QuarkClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkTCPNetworkTransport;

use Quark\Extensions\PushNotification\IQuarkPushNotificationProvider;

use Quark\Extensions\PushNotification\Device;

/**
 * Class AppleAPNS
 *
 * @package Quark\Extensions\PushNotification\Providers
 */
class AppleAPNS extends QuarkJSONIOProcessor implements IQuarkPushNotificationProvider {
	const TYPE = 'ios';

	const OPTION_CERTIFICATE = 'certificate';

	const OPTION_PRODUCTION = 'ssl://gateway.push.apple.com:2195';
	const OPTION_SANDBOX = 'ssl://gateway.sandbox.push.apple.com:2195';

	const OPTION_ALERT = 'alert';
	const OPTION_BADGE = 'badge';
	const OPTION_SOUND = 'sound';

	/**
	 * @var QuarkCertificate $_certificate
	 */
	private $_certificate;

	/**
	 * @var string $_host = self::OPTION_PRODUCTION
	 */
	private $_host = self::OPTION_PRODUCTION;

	/**
	 * @var Device[] $_devices = []
	 */
	private $_devices = array();

	/**
	 * @var array $_payload = []
	 */
	private $_payload = array();

	/**
	 * @return string
	 */
	public function Type () {
		return self::TYPE;
	}

	/**
	 * @param $config
	 */
	public function Config ($config) {
		if (isset($config[self::OPTION_CERTIFICATE]) && $config[self::OPTION_CERTIFICATE] instanceof QuarkCertificate)
			$this->_certificate = $config[self::OPTION_CERTIFICATE];

		if (isset($config[self::OPTION_SANDBOX]) && $config[self::OPTION_SANDBOX] == true)
			$this->_host = self::OPTION_SANDBOX;
	}

	/**
	 * @param Device $device
	 */
	public function Device (Device $device) {
		if (!preg_match('#^[a-f0-9\<\> ]+$#Uis', $device->id)) {
			Quark::Log('[AppleAPNS] Invalid device id "' . $device->id . '"', Quark::LOG_WARN);
			return;
		}

		$this->_devices[] = $device;
	}

	/**
	 * @return Device[]
	 */
	public function Devices () {
		return $this->_devices;
	}

	/**
	 * @param object|array $payload
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function Send ($payload, $options = []) {
		if ($this->_certificate == null) return false;

		$alert = isset($options[self::OPTION_ALERT]) ? $options[self::OPTION_ALERT] : '';
		$data = $payload;

		if (is_scalar($payload)) {
			$alert = $payload;
			$data = array();
		}

		$this->_payload = array(
			'aps' => array(
				'alert' => $alert,
				'badge' => isset($options[self::OPTION_BADGE]) ? $options[self::OPTION_BADGE] : 1,
				'sound' => isset($options[self::OPTION_SOUND]) ? $options[self::OPTION_SOUND] : 'default'
			),
			'data' => $data
		);

		$client = new QuarkClient($this->_host, new QuarkTCPNetworkTransport(), $this->_certificate, 60, false);

		$client->Flags(STREAM_CLIENT_ASYNC_CONNECT);

		$client->On(QuarkClient::EVENT_CONNECT, function (QuarkClient $client) {
			foreach ($this->_devices as $device)
				$client->Send($this->_msg($device));
		});

		$client->On(QuarkClient::EVENT_ERROR_CONNECT, function ($error) {
			Quark::Log($error, Quark::LOG_WARN);
		});

		return $client->Connect();
	}

	/**
	 * @return mixed
	 */
	public function Reset () {
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