<?php
namespace Quark\Extensions\Analytics\Providers;

use Quark\IQuarkViewResource;
use Quark\QuarkGenericViewResource;

use Quark\Extensions\Analytics\IQuarkAnalyticsProvider;

/**
 * Class YandexMetrika
 *
 * @package Quark\Extensions\Analytics\Providers
 */
class YandexMetrika implements IQuarkAnalyticsProvider {
	const TYPE = 'analytics.ym';

	const OPTION_ID = 'option.id';
	const OPTION_WEBVISOR = 'option.webvisor';

	const INI_ID = 'analytics.ym.id';
	const INI_WEBVISOR = 'analytics.ym.webvisor';

	/**
	 * @var string $_id = ''
	 */
	private $_id = '';

	/**
	 * @var bool $_webvisor = false
	 */
	private $_webvisor = false;

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

		if (isset($config[self::OPTION_WEBVISOR]))
			$this->_webvisor = $config[self::OPTION_WEBVISOR];
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

			case self::INI_WEBVISOR:
				$this->_webvisor = $value == '1';
				break;

			default: break;
		}
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function AnalyticsProviderViewDependencies () {
		$js = QuarkGenericViewResource::ForeignJS('https://mc.yandex.ru/metrika/watch.js');
		$js->Type()->Async(true);

		return array($js);
	}

	/**
	 * @return string
	 */
	public function AnalyticsProviderViewFragment () {
		return '
			<script type="text/javascript">
				window[\'yandex_metrika_callbacks\'].push(function() {
					window.yaCounter' . $this->_id . ' = new Ya.Metrika({
						id:' . $this->_id . ',
						clickmap:true,
						trackLinks:true,
						accurateTrackBounce:true,
						webvisor:' . ($this->_webvisor ? 'true' : 'false') . '
					});
				});
			</script>
		';
	}
}