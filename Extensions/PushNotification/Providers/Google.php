<?php
namespace Quark\Extensions\PushNotification\Providers;

use Quark\Extensions\PushNotification\Device;
use Quark\Extensions\PushNotification\IPushNotificationProvider;

use Quark\QuarkClientDTO;
use Quark\QuarkPlainIOProcessor;
use Quark\QuarkJSONIOProcessor;

/**
 * Class Google
 *
 * @package Quark\Extensions\PushNotification\Providers
 */
class Google implements IPushNotificationProvider {
	private $_device = null;
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
		return 'android';
	}

	/**
	 * @return string
	 */
	public function URL () {
		return 'https://android.googleapis.com/gcm/send';
	}

	/**
	 * @param Device $device
	 */
	public function Device (Device $device) {
		$this->_device = $device;
	}

	/**
	 * @param $payload
	 *
	 * @return QuarkClientDTO
	 */
	public function Request ($payload) {
		return new QuarkClientDTO(
			array(
				'Authorization' => 'key='. $this->_key
			),
			array(
				'registration_ids' => array($this->_device),
				'data' => $payload,
			),
			new QuarkJSONIOProcessor()
		);
	}

	/**
	 * @return QuarkClientDTO
	 */
	public function Response () {
		$response = new QuarkClientDTO();
		$response->Processor(new QuarkPlainIOProcessor());

		return $response;
	}
}