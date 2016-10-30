<?php
namespace Quark\Extensions\PushNotification\Providers;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;

use Quark\Extensions\PushNotification\IQuarkPushNotificationProvider;

use Quark\Extensions\PushNotification\Device;

/**
 * Class GoogleGCM
 *
 * @package Quark\Extensions\PushNotification\Providers
 */
class GoogleGCM implements IQuarkPushNotificationProvider {
	const TYPE = 'android';
	const BULK_MAX = 1000;

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
	public function PNPSend($payload, $options = []) {
		$size = sizeof($this->_devices);
		if ($size == 0) return true;

		$i = 0;
		$queues = ceil($size / self::BULK_MAX);

		while ($i < $queues) {
			$request = QuarkDTO::ForPOST(new QuarkJSONIOProcessor());
			$request->Header(QuarkDTO::HEADER_AUTHORIZATION, 'key=' . $this->_key);
			$request->Data(array(
				'registration_ids' => array_slice($this->_devices, $i * self::BULK_MAX, self::BULK_MAX),
				'data' => $payload,
			));

			$response = new QuarkDTO(new QuarkJSONIOProcessor());

			$out = QuarkHTTPClient::To('https://android.googleapis.com/gcm/send', $request, $response);

			if (!$out || $out->success != 1) {
				Quark::Log('[GoogleGCM] Error during sending push notification. Google GCM response: ' . print_r($out, true));
				return false;
			}

			$i++;
		}

		return true;
	}

	/**
	 * @return void
	 */
	public function PNPReset () {
		$this->_devices = array();
	}
}