<?php
namespace Quark\Extensions\PushNotification\Providers;

use Quark\QuarkClient;
use Quark\QuarkDTO;
use Quark\QuarkHTTPTransportClient;
use Quark\QuarkJSONIOProcessor;

use Quark\Extensions\PushNotification\Device;
use Quark\Extensions\PushNotification\IQuarkPushNotificationProvider;

/**
 * Class Google
 *
 * @package Quark\Extensions\PushNotification\Providers
 */
class Google implements IQuarkPushNotificationProvider {
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
	 * @param $payload
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Send($payload, $options = []) {
		$request = QuarkDTO::ForPOST(new QuarkJSONIOProcessor());
		$request->Header('Authorization', 'key=' . $this->_key);
		$request->Data(array(
			'registration_ids' => $this->_devices,
			'data' => $payload,
		));

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		$client = new QuarkClient('https://android.googleapis.com/gcm/send', new QuarkHTTPTransportClient($request, $response));

		$client->Action();

		return true;
	}

	/**
	 * @return mixed
	 */
	public function Reset () {
		$this->_devices = array();
	}
}