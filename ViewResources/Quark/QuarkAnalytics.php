<?php
namespace Quark\ViewResources\Quark;

use Quark\IQuarkInlineViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;
use Quark\QuarkSource;

/**
 * Class QuarkAnalytics
 *
 * @package Quark\ViewResources\Quark
 */
class QuarkAnalytics implements IQuarkViewResource, IQuarkInlineViewResource {
	/**
	 * @var string $_ga
	 */
	private $_ga = '';

	/**
	 * @var string $_ym
	 */
	private $_ym = '';

	/**
	 * @var bool $_ym_webvisor = false
	 */
	private $_ym_webvisor = false;

	/**
	 * @param string $ga
	 * @param string $ym
	 * @param bool $ym_webvisor = false
	 */
	public function __construct ($ga = '', $ym = '', $ym_webvisor = false) {
		$this->_ga = $ga;
		$this->_ym = $ym;
		$this->_ym_webvisor = $ym_webvisor;
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

		if (strlen(trim($this->_ga)) != 0)
			$out .= '
				<script type="text/javascript">
					(function(i,s,o,g,r,a,m){i[\'GoogleAnalyticsObject\']=r;i[r]=i[r]||function(){
					(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
					m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
					})(window,document,\'script\',\'//www.google-analytics.com/analytics.js\',\'ga\');

					ga(\'create\', \'' . $this->_ga . '\', \'auto\');
					ga(\'send\', \'pageview\');
				</script>
			';

		if (strlen(trim($this->_ym)) != 0)
			$out .= '
				<script type="text/javascript">
					(function (d, w, c) {
						(w[c] = w[c] || []).push(function() {
							try {
								w.yaCounter' . $this->_ym . ' = new Ya.Metrika({
									id:' . $this->_ym . ',
									clickmap:true,
									trackLinks:true,
									accurateTrackBounce:true,
									webvisor:' . ($this->_ym_webvisor ? 'true' : 'false') . '
								});
							} catch(e) { }
						});

						var n = d.getElementsByTagName("script")[0],
							s = d.createElement("script"),
							f = function () { n.parentNode.insertBefore(s, n); };
						s.type = "text/javascript";
						s.async = true;
						s.src = "https://mc.yandex.ru/metrika/watch.js";

						if (w.opera == "[object Opera]") {
							d.addEventListener("DOMContentLoaded", f, false);
						} else { f(); }
					})(document, window, "yandex_metrika_callbacks");
				</script>
				<noscript><div><img src="https://mc.yandex.ru/watch/34551500" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
			';

		return QuarkSource::ObfuscateString($out);
	}
}