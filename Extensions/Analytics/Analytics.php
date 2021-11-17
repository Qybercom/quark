<?php
namespace Quark\Extensions\Analytics;

use Quark\IQuarkExtension;
use Quark\IQuarkInlineViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\Quark;
use Quark\QuarkMinimizableViewResourceBehavior;

/**
 * Class Analytics
 *
 * @package Quark\Extensions\Analytics
 */
class Analytics implements IQuarkExtension, IQuarkViewResource, IQuarkInlineViewResource, IQuarkViewResourceWithDependencies {
	use QuarkMinimizableViewResourceBehavior;

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
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		$out = array();
		$providers = $this->_config->Providers();

		foreach ($providers as $i => &$provider)
			$out = array_merge($out, $provider->AnalyticsProviderViewDependencies());

		return $out;
	}

	/**
	 * @param bool $minimize
	 *
	 * @return string
	 */
	public function HTML ($minimize) {
		$out = '';
		$providers = $this->_config->Providers();

		foreach ($providers as $i => &$provider)
			$out .= $provider->AnalyticsProviderViewFragment();

		return $minimize ? $this->MinimizeString($out) : $out;
	}
}