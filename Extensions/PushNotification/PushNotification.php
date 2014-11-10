<?php
namespace Quark\Extensions\PushNotification;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;
use Quark\QuarkClient;
use Quark\QuarkClientDTO;
use Quark\QuarkCredentials;

/**
 * Class PushNotification
 *
 * @package Quark\Extensions\PushNotification
 */
class PushNotification implements IQuarkExtension {
	/**
	 * @var Config
	 */
	private static $_config;

	/**
	 * @var array
	 */
	private $_payload = array();
	private $_devices = array();
	private $_options = array();

	/**
	 * @var QuarkClient|null
	 */
	private $_client = null;

	/**
	 * @param IQuarkExtensionConfig|Config|null $config
	 *
	 * @return mixed
	 */
	static function Config ($config) {
		self::$_config = $config;
	}

	/**
	 * @param array $payload
	 */
	public function __construct ($payload = []) {
		$this->_payload = $payload;

		$this->_client = new QuarkClient();
		$this->_client->Sign('wpc_pass', 'D:/dev/onwheels/web/server.pem');
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
	 * @param string $type
	 * @param array $opt
	 *
	 * @return array
	 */
	public function Options ($type = '', $opt = []) {
		$args = func_num_args();

		if ($args == 0)
			return $this->_options;

		if ($args == 2)
			$this->_options[$type] = $opt;

		return isset($this->_options[$type])
			? $this->_options[$type]
			: array();
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
		if (!(self::$_config instanceof Config)) return false;

		$providers = self::$_config->Providers();

		foreach ($this->_devices as $i => $device) {
			foreach ($providers as $p => $provider) {
				/**
				 * @var $provider IPushNotificationProvider
				 */

				if ($provider->Type() != $device->type) continue;

				$this->_client->Reset();

				$provider->Device($device);

				$this->_client->Credentials(QuarkCredentials::FromURI($provider->URL()));
				$this->_client->Request($provider->Request());
				$this->_client->Response($provider->Response());
				$this->_client->Post();
				//print_r($this->_client);
			}
		}

		return true;
	}
}