<?php
namespace Quark\Extensions\PushNotification;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;

/**
 * Class PushNotificationConfig
 *
 * @package Quark\Extensions\PushNotification
 */
class PushNotificationConfig implements IQuarkExtensionConfig {
	/**
	 * @var IQuarkPushNotificationProvider[] $_providers
	 */
	private $_providers = array();

	/**
	 * @var string $_name
	 */
	private $_name = '';

	/**
	 * @param IQuarkPushNotificationProvider $provider
	 * @param $config
	 *
	 * @return $this
	 */
	public function Provider (IQuarkPushNotificationProvider $provider, $config) {
		$provider->Config($config);
		$this->_providers[] = $provider;

		return $this;
	}

	/**
	 * @return IQuarkPushNotificationProvider[]
	 */
	public function Providers () {
		return $this->_providers;
	}

	/**
	 * @param string $name
	 */
	public function Stacked ($name) {
		$this->_name = $name;
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new PushNotification($this->_name);
	}
}