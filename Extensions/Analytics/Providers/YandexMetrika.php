<?php
namespace Quark\Extensions\Analytics\YandexMetrika;

use Quark\IQuarkViewResource;

use Quark\Extensions\Analytics\IQuarkAnalyticsProvider;

/**
 * Class YandexMetrika
 *
 * @package Quark\Extensions\Analytics\YandexMetrika
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
		return array();
	}

	/**
	 * @return string
	 */
	public function AnalyticsProviderViewFragment () {
		return '
			<script type="text/javascript">
				window.addEventListener(\'load\', function () {
					if (typeof(ym) == \'function\') return;
					if (typeof(Ya) != \'undefined\') return;

					(function (m, e, t, r, i, k, a) {
						m[i] = m[i] || function () {
							(m[i].a = m[i].a || []).push(arguments);
						};
						m[i].l = 1 * new Date();
						k = e.createElement(t),
						k.async = 1,
						k.src = r,
						a = e.getElementsByTagName(t)[0],
						a.parentNode.insertBefore(k,a);
					})
					(window, document, \'script\', \'https://mc.yandex.ru/metrika/tag.js\', \'ym\');

					ym(' . $this->_id . ', \'init\', {
						clickmap: true,
						trackLinks: true,
						accurateTrackBounce: true,
						webvisor: ' . ($this->_webvisor ? 'true' : 'false') . '
					});
				});
			</script>
		';
	}
}