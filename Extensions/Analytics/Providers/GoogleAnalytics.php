<?php
namespace Quark\Extensions\Analytics\Providers;

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
	 * @return string
	 */
	public function AnalyticsProviderViewFragment () {
		return '
			<script type="text/javascript">
				(function(i,s,o,g,r,a,m){i[\'GoogleAnalyticsObject\']=r;i[r]=i[r]||function(){
				(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
				m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
				})(window,document,\'script\',\'//www.google-analytics.com/analytics.js\',\'ga\');

				ga(\'create\', \'' . $this->_id . '\', \'auto\');
				ga(\'send\', \'pageview\');
			</script>
		';
	}
}