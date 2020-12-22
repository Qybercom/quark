<?php
namespace Quark\Extensions\Analytics\GoogleAnalytics;

use Quark\IQuarkViewResource;

use Quark\Extensions\Analytics\IQuarkAnalyticsProvider;

/**
 * Class GoogleAnalytics
 *
 * @package Quark\Extensions\Analytics\GoogleAnalytics
 */
class GoogleAnalytics implements IQuarkAnalyticsProvider {
	const TYPE = 'analytics.ga';

	const OPTION_ID = 'option.id';

	const INI_ID = 'analytics.ym.id';

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
		if (isset($config[self::OPTION_ID]))
			$this->_id = $config[self::OPTION_ID];
	}

	/**
	 * @param string $key
	 * @param $value
	 *
	 * @return mixed
	 */
	public function AnalyticsProviderOption ($key, $value) {
		switch ($key) {
			case self::INI_ID:
				$this->_id = $value;
				break;

			default: break;
		}
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function AnalyticsProviderViewDependencies () {
		return array();
	}

	/**
	 * @return string
	 */
	public function AnalyticsProviderViewFragment () {
		return '
			<script>
				window.addEventListener(\'load\', function () {
					if (typeof(gtag) != \'function\') {
						var script_gtag = document.createElement(\'script\');
						script_gtag.async = true;
						script_gtag.src = \'https://www.googletagmanager.com/gtag/js?id=' . $this->_id . '\';

						document.getElementsByTagName(\'head\')[0].appendChild(script_gtag);

						window.dataLayer = window.dataLayer || [];
						function gtag(){dataLayer.push(arguments);}
						gtag(\'js\', new Date());
					}

					if (typeof(ga) != \'function\') {
						var script_ga = document.createElement(\'script\');
						script_ga.async = true;
						script_ga.src = \'https://www.google-analytics.com/analytics.js\';

						document.getElementsByTagName(\'head\')[0].appendChild(script_ga);
					}

					gtag(\'config\', \'' . $this->_id . '\');

					window.ga=window.ga||function(){(ga.q=ga.q||[]).push(arguments)};ga.l=+new Date;
				});
			</script>
			<script type="text/javascript" src="https://www.google-analytics.com/analytics.js" async="async"></script>
		';
	}
}