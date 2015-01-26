<?php
namespace Quark\Extensions\PushNotification;

use Quark\IQuarkExtension;

use Quark\Quark;
use Quark\QuarkClient;
use Quark\QuarkCredentials;

/**
 * Class PushNotification
 *
 * @package Quark\Extensions\PushNotification
 */
class PushNotification implements IQuarkExtension {
	private static $_providers = array();

	/**
	 * @param IPushNotificationProvider $provider
	 * @param $config
	 */
	public function Provider (IPushNotificationProvider $provider, $config = []) {
		$provider->Config($config);
		self::$_providers[] = $provider;
	}

	/**
	 * @var array
	 */
	private $_payload = array();
	private $_devices = array();

	/**
	 * @var QuarkClient
	 */
	private $_client = null;

	/**
	 * @param array $payload
	 */
	public function __construct ($payload = []) {
		$this->_payload = $payload;

		$this->_client = new QuarkClient();
	}

	/**
	 * @return QuarkClient
	 */
	public function Client () {
		return $this->_client;
	}

	/**
	 * @param array $payload
	 *
	 * @return array
	 */
	public function Payload ($payload = []) {
		if (func_num_args() == 1)
			$this->_payload = $payload;

		return $this->_payload;
	}

	/**
	 * @param Device $device
	 */
	public function Device (Device $device) {
		$this->_devices[] = $device;
	}

	/**
	 * @return bool
	 */
	public function Send () {
		$ok = true;

		foreach ($this->_devices as $device) {
			foreach (self::$_providers as $provider) {
				/**
				 * @var $provider IPushNotificationProvider
				 */

				if ($provider->Type() != $device->type) continue;

				$provider->Device($device);

				$this->_client->Reset();
				$this->_client->Credentials(QuarkCredentials::FromURI($provider->URL()));
				$this->_client->Request($provider->Request($this->_payload));
				$this->_client->Response($provider->Response());
				$response = $this->_client->Post();

				$status = $this->_client->Response()->Status()->code == 200;

				if (!$status)
					Quark::Log("Push notification failed. Response:\r\n" . print_r($response, true), Quark::LOG_WARN);

				$ok &= $status;
			}
		}

		return $ok;
	}
}