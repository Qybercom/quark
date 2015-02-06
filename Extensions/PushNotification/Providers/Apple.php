<?php
namespace Quark\Extensions\PushNotification\Providers;

use Quark\IQuarkTransportProvider;

use Quark\Quark;
use Quark\QuarkCertificate;
use Quark\QuarkClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkURI;

use Quark\Extensions\PushNotification\Device;
use Quark\Extensions\PushNotification\IPushNotificationProvider;

/**
 * Class Apple
 *
 * @package Quark\Extensions\PushNotification\Providers
 */
class Apple extends QuarkJSONIOProcessor implements IPushNotificationProvider, IQuarkTransportProvider {
	const TYPE = 'ios';

	const OPTION_CERTIFICATE = 'certificate';

	/**
	 * @var QuarkURI $_uri
	 */
	private $_uri;

	/**
	 * @var QuarkCertificate $_certificate
	 */
	private $_certificate;

	/**
	 * @var Device[] $_devices
	 */
	private $_devices = array();

	/**
	 * @var array $_payload
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
	}

	/**
	 * @param Device $device
	 */
	public function Device (Device $device) {
		$this->_devices[] = $device;
	}

	/**
	 * @param $payload
	 *
	 * @return mixed
	 */
	public function Send ($payload) {
		if ($this->_certificate == null) return false;

		if (is_scalar($payload))
			$payload = array(
				'aps' => array(
					'alert' => $payload
				)
			);

		$this->_payload = Quark::Normalize($payload, array(
			'aps' => array(
				'alert' => '',
				'badge' => 1,
				'sound' => 'default'
			)
		));

		$client = new QuarkClient('ssl://gateway.push.apple.com:2195', $this, $this->_certificate);
		$client->Action();

		return true;
	}

	/**
	 * @param QuarkURI         $uri
	 * @param QuarkCertificate $certificate
	 *
	 * @return mixed
	 */
	public function Setup (QuarkURI $uri, QuarkCertificate $certificate) {
		$this->_uri = $uri;
	}

	/**
	 * @param Device $device
	 *
	 * @return string
	 */
	private function _msg (Device $device) {
		$payload = $this->Encode($this->_payload);

		return chr(0) . pack('n', 32) . pack('H*', $device->id) . pack('n', strlen($payload)) . $payload;
	}

	/**
	 * @param QuarkClient $client
	 *
	 * @return mixed
	 */
	public function Action (QuarkClient $client) {
		if (!$client->Connect()) return false;

		foreach ($this->_devices as $device)
			$client->Send($this->_msg($device));

		$client->Close();

		return true;
	}
}