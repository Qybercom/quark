<?php
namespace Quark\Extensions\PushNotification;

use Quark\IQuarkExtensionConfig;

/**
 * Class Config
 *
 * @package Quark\Extensions\PushNotification
 */
class Config implements IQuarkExtensionConfig {
	private $_providers = array();

	/**
	 * @return string
	 */
	public function AssignedExtension () {
		return 'Quark\Extensions\PushNotification\PushNotification';
	}

	/**
	 * @param IPushNotificationProvider $provider
	 */
	public function Provider (IPushNotificationProvider $provider) {
		$provider->Config();
		$this->_providers[] = $provider;
	}

	/**
	 * @return array
	 */
	public function Providers () {
		return $this->_providers;
	}
}