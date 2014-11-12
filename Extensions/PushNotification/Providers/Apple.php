<?php
namespace Quark\Extensions\PushNotification\Providers;

use Quark\Extensions\PushNotification\Device;
use Quark\Extensions\PushNotification\IPushNotificationProvider;

use Quark\QuarkClientDTO;
use Quark\QuarkCertificate;
use Quark\QuarkPlainIOProcessor;

/**
 * Class Apple
 *
 * @package Quark\Extensions\PushNotification\Providers
 */
class Apple implements IPushNotificationProvider {
	private $_settings = array();
	private $_certificate = '';
	private $_device = '';

	/**
	 * @return string
	 */
	public function Type () {
		return 'ios';
	}

	/**
	 * @param $config
	 */
	public function Config ($config) {
		if (isset($config['settings']) && is_array($config['settings']))
			$this->_settings = $config['settings'];

		if (isset($config['certificate']) && $config['certificate'] instanceof QuarkCertificate)
			$this->_certificate = $config['certificate'];
	}

	/**
	 * @return string
	 */
	public function URL () {
		return 'ssl://gateway.push.apple.com:2195';
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
		// TODO: Implement Request() method.
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