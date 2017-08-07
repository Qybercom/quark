<?php
namespace Quark\Extensions\Analytics;

use Quark\IQuarkViewResource;

/**
 * Interface IQuarkAnalyticsProvider
 *
 * @package Quark\Extensions\Analytics
 */
interface IQuarkAnalyticsProvider {
	/**
	 * @return string
	 */
	public function AnalyticsProviderType();

	/**
	 * @param $config
	 */
	public function AnalyticsProviderConfig($config);

	/**
	 * @param string $key
	 * @param $value
	 *
	 * @return mixed
	 */
	public function AnalyticsProviderOption($key, $value);

	/**
	 * @return IQuarkViewResource[]
	 */
	public function AnalyticsProviderViewDependencies();

	/**
	 * @return string
	 */
	public function AnalyticsProviderViewFragment();
}