<?php
namespace Quark\Extensions\Analytics;

use Quark\IQuarkExtension;
use Quark\IQuarkInlineViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;

use Quark\Quark;
use Quark\QuarkSource;

/**
 * Class Analytics
 *
 * @package Quark\Extensions\Analytics
 */
class Analytics implements IQuarkExtension, IQuarkViewResource, IQuarkInlineViewResource {
	/**
	 * @var AnalyticsConfig $_config
	 */
	private $_config;

	/**
	 * @param string $config
	 */
	public function __construct ($config) {
		$this->_config = Quark::Config()->Extension($config);
	}

	/**
	 * @return IQuarkViewResourceType
	 */
	public function Type () {
		// TODO: Implement Type() method.
	}

	/**
	 * @return string
	 */
	public function Location () {
		// TODO: Implement Location() method.
	}

	/**
	 * @return string
	 */
	public function HTML () {
		$out = '';
		$providers = $this->_config->Providers();

		foreach ($providers as $provider)
			$out .= $provider->AnalyticsProviderViewFragment();

		return QuarkSource::ObfuscateString($out);
	}
}