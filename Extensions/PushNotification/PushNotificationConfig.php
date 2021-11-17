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
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var IQuarkPushNotificationProvider $_provider = null
	 */
	private $_provider = null;

	/**
	 * @param IQuarkPushNotificationProvider $provider
	 */
	public function __construct (IQuarkPushNotificationProvider $provider) {
		$this->Provider($provider);
	}

	/**
	 * @param IQuarkPushNotificationProvider $provider = null
	 *
	 * @return IQuarkPushNotificationProvider
	 */
	public function &Provider (IQuarkPushNotificationProvider $provider = null) {
		if ($provider != null)
			$this->_provider = $provider;

		return $this->_provider;
	}

	/**
	 * @param string $name
	 */
	public function Stacked ($name) {
		$this->_name = $name;
	}

	/**
	 * @return string
	 */
	public function ExtensionName () {
		return $this->_name;
	}

	/**
	 * @param object $ini
	 *
	 * @return void
	 */
	public function ExtensionOptions ($ini) {
		$properties = $this->_provider->PushNotificationProviderProperties();

		foreach ($properties as $i => &$property)
			if (isset($ini->$property))
				$this->_provider->$property($ini->$property);

		unset($i, $property, $properties);

		$this->_provider->PushNotificationProviderInit($this->_name);
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		// TODO: Implement ExtensionInstance() method.
	}
}