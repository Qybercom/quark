<?php
namespace Quark\Extensions\Analytics;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;

/**
 * Class AnalyticsConfig
 *
 * @package Quark\Extensions\Analytics
 */
class AnalyticsConfig implements IQuarkExtensionConfig {
	/**
	 * @var IQuarkAnalyticsProvider[] $_providers = []
	 */
	private $_providers = array();

	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @param IQuarkAnalyticsProvider $provider
	 * @param $config
	 *
	 * @return $this
	 */
	public function Provider (IQuarkAnalyticsProvider $provider, $config = null) {
		if (func_num_args() == 2)
			$provider->AnalyticsProviderConfig($config);

		$this->_providers[] = $provider;

		return $this;
	}

	/**
	 * @return IQuarkAnalyticsProvider[]
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
				if ($provider->AnalyticsProviderType() == $type)
					$provider->AnalyticsProviderOption($key, $value);
		}
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new Analytics($this->_name);
	}
}