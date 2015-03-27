<?php
namespace Quark\Extensions\PushNotification;

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
}