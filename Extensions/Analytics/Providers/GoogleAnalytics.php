<?php
namespace Quark\Extensions\Analytics\Providers;

use Quark\IQuarkViewResource;
use Quark\QuarkGenericViewResource;

use Quark\Extensions\Analytics\IQuarkAnalyticsProvider;

/**
 * Class GoogleAnalytics
 *
 * @package Quark\Extensions\Analytics\Providers
 */
class GoogleAnalytics implements IQuarkAnalyticsProvider {
	const TYPE = 'analytics.ga';

	/**
	 * @var string $_id = ''
	 */
	private $_id = '';

	/**
	 * @return string
	 */
	public function AnalyticsProviderType () {
		return self::TYPE;
	}

	/**
	 * @param $config
	 */
	public function AnalyticsProviderConfig ($config) {
		if (is_string($config))
			$this->_id = $config;
	}

	/**
	 * @param string $key
	 * @param $value
	 *
	 * @return mixed
	 */
	public function AnalyticsProviderOption ($key, $value) {
		if (is_string($value))
			$this->_id = $value;
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function AnalyticsProviderViewDependencies () {
		$js = QuarkGenericViewResource::ForeignJS('//www.google-analytics.com/analytics.js');
		$js->Type()->Async(true);

		return array($js);
	}

	/**
	 * @return string
	 */
	public function AnalyticsProviderViewFragment () {
		return '
			<script type="text/javascript">
				var GoogleAnalyticsObject = \'ga\',
					ga = ga || function () {
						(ga.q = ga.q || []).push(arguments);
					};

				ga(\'create\', \'' . $this->_id . '\', \'auto\');
				ga(\'send\', \'pageview\');
			</script>
		';
	}
}