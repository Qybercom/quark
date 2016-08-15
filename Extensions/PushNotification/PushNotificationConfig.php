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
	 * @var IQuarkPushNotificationProvider[] $_providers = []
	 */
	private $_providers = array();

	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @param IQuarkPushNotificationProvider $provider
	 * @param $config
	 *
	 * @return $this
	 */
	public function Provider (IQuarkPushNotificationProvider $provider, $config = null) {
		if (func_num_args() == 2)
			$provider->PNPConfig($config);

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
	 * @return string
	 */
	public function ExtensionName () {
		return $this->_name;
	}

	/**
	 * @param object $ini
	 *
	 * @return mixed
	 */
	public function ExtensionOptions ($ini) {
		foreach ($ini as $key => $value) {
			$type = explode('.', $key)[0];

			foreach ($this->_providers as $provider)
				if ($provider->PNPType() == $type)
					$provider->PNPOption($key, $value);
		}
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new PushNotification($this->_name);
	}
}