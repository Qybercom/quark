<?php
namespace Quark\Extensions\PushNotification\Providers;

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
		if (sizeof($this->_devices) == 0) return true;

		$request = QuarkDTO::ForPOST(new QuarkJSONIOProcessor());
		$request->Header(QuarkDTO::HEADER_AUTHORIZATION, 'key=' . $this->_key);
		$request->Data(array(
			'registration_ids' => $this->_devices,
			'data' => $payload,
		));

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		$out = QuarkHTTPClient::To('https://android.googleapis.com/gcm/send', $request, $response);

		return $out && $out->success == 1;
	}

	/**
	 * @return void
	 */
	public function PNPReset () {
		$this->_devices = array();
	}
}