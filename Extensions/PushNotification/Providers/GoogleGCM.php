<?php
namespace Quark\Extensions\PushNotification\Providers;

use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;

use Quark\Extensions\PushNotification\Device;
use Quark\Extensions\PushNotification\IQuarkPushNotificationProvider;

/**
 * Class GoogleGCM
 *
 * @package Quark\Extensions\PushNotification\Providers
 */
class GoogleGCM implements IQuarkPushNotificationProvider {
	const TYPE = 'android';

	private $_devices = array();
	private $_key = '';

	/**
	 * @param $config
	 */
	public function Config ($config) {
		if (is_string($config))
			$this->_key = $config;
	}

	/**
	 * @return string
	 */
	public function Type () {
		return self::TYPE;
	}

	/**
	 * @param Device $device
	 */
	public function Device (Device $device) {
		$this->_devices[] = $device->id;
	}

	/**
	 * @param object|array $payload
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function Send($payload, $options = []) {
		if (sizeof($this->_devices) == 0) return true;

		$request = QuarkDTO::ForPOST(new QuarkJSONIOProcessor());
		$request->Header(QuarkDTO::HEADER_AUTHORIZATION, 'key=' . $this->_key);
		$request->Data(array(
			'registration_ids' => $this->_devices,
			'data' => $payload,
		));

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		return QuarkHTTPClient::To('https://android.googleapis.com/gcm/send', $request, $response)->success == 1;
	}

	/**
	 * @return mixed
	 */
	public function Reset () {
		$this->_devices = array();
	}
}