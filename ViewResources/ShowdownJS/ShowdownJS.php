<?php
namespace Quark\ViewResources\ShowdownJS;

use Quark\IQuarkForeignViewResource;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkDTO;
use Quark\QuarkJSViewResourceType;
use Quark\QuarkLexingViewResourceBehavior;

/**
 * Class ShowdownJS
 *
 * @package Quark\ViewResources\ShowdownJS
 */
class ShowdownJS implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource {
	use QuarkLexingViewResourceBehavior;

	const CURRENT_VERSION = '1.4.0';

	/**
	 * @var string $_version = self::CURRENT_VERSION
	 */
	private $_version = self::CURRENT_VERSION;

	/**
	 * @param string $version = self::CURRENT_VERSION
	 */
	public function __construct ($version = self::CURRENT_VERSION) {
		$this->_version = $version;
	}

	/**
	 * @return IQuarkViewResourceType
	 */
	public function Type () {
		return new QuarkJSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return 'https://cdnjs.cloudflare.com/ajax/libs/showdown/' . $this->_version . '/showdown.min.js';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}

	/**
	 * @param string $content
	 * @param string $delimiter
	 *
	 * @return string
	 */
	private static function _code ($content, $delimiter) {
		return preg_replace_callback(
			'#' . $delimiter . '{3,}(.*)\n(.*)' . $delimiter . '{3,}#Uis',
			function ($matches) {
				$matches[2] = str_replace('<', '&lt;', $matches[2]);
				$matches[2] = str_replace('*', '&#42;', $matches[2]);
				$matches[2] = str_replace('    ', "\t", $matches[2]);

				return '<pre><code lang="' . $matches[1] . '" class="' . $matches[1] . '">' . trim($matches[2], "\r\0\x0B") . '</code></pre>';
			},
			$content
		);
	}

	/**
	 * @param string $content = ''
	 * @param bool $full = false
	 * @param string $css = ''
	 *
	 * @return string
	 */
	public static function ToHTML ($content = '', $full = false, $css = '') {
		$content = "\r\n" . $content . "\r\n";

		$content = preg_replace('#\#\#\#\#\#\#(.*)\n#', '<h6>$1</h6>', $content);
		$content = preg_replace('#\#\#\#\#\#(.*)\n#', '<h5>$1</h5>', $content);
		$content = preg_replace('#\#\#\#\#(.*)\n#', '<h4>$1</h4>', $content);
		$content = preg_replace('#\#\#\#(.*)\n#', '<h3>$1</h3>', $content);
		$content = preg_replace('#\#\#(.*)\n#', '<h2>$1</h2>', $content);
		$content = preg_replace('#\#(.*)\n#', '<h1>$1</h1>', $content);

		$content = preg_replace('#\#\#\#\#\#\#(.*)\#\#\#\#\#\##Uis', '<h6>$1</h6>', $content);
		$content = preg_replace('#\#\#\#\#\#(.*)\#\#\#\#\##Uis', '<h5>$1</h5>', $content);
		$content = preg_replace('#\#\#\#\#(.*)\#\#\#\##Uis', '<h4>$1</h4>', $content);
		$content = preg_replace('#\#\#\#(.*)\#\#\##Uis', '<h3>$1</h3>', $content);
		$content = preg_replace('#\#\#(.*)\#\##Uis', '<h2>$1</h2>', $content);
		$content = preg_replace('#\#(.*)\##Uis', '<h1>$1</h1>', $content);

		$content = preg_replace('#\*{3,}#', '<hr />', $content);

		$content = self::_code($content, '\`');
		$content = self::_code($content, '\~');

		$content = preg_replace_callback('#\`(.*)\`#U', function ($matches) {
			return '<code>' . trim($matches[1]) . '</code>';
		}, $content);

		$content = preg_replace('#(.*)\n\=+#', '<h1>$1</h1>', $content);
		$content = preg_replace('#(.*)\n\-+#', '<h2>$1</h2>', $content);

		$content = preg_replace_callback('#\n    (.*)#', function ($matches) {
			$matches[1] = str_replace('    ', "\t", $matches[1]);

			return '<pre><code>' . trim($matches[1], "\r\0\x0B") . '</code></pre>';
		}, $content);

		$content = preg_replace('#\n\>(.*)#', '<blockquote>$1</blockquote>', $content);
		$content = preg_replace('#\n ?[\-\*] (.*)#', '<ul><li>$1</li></ul>', $content);
		$content = preg_replace('#\n ?[0-9*]\. (.*)#', '<ol><li>$1</li></ol>', $content);

		$content = preg_replace('#\!\[(.*)\]\((.*)\)#Uis', '<img src="$2" class="showdown-image" alt="$1" />', $content);
		$content = preg_replace('#\[(.*)\]\((.*)\)#Uis', '<a href="$2" class="quark-button">$1</a>', $content);
		$content = preg_replace('#\s(https?)\:\/\/([a-zA-Z0-9\-\%\&\#\/\.]*)\s#Uis', ' <a href="$1://$2" class="quark-button">$1://$2</a> ', $content);

		$content = str_replace('</blockquote><blockquote>', '<br />', $content);
		$content = str_replace('</code></pre><pre><code>', "\r\n", $content);
		$content = str_replace('</li></ul><ul><li>', '</li><li>', $content);
		$content = str_replace('</li></ol><ol><li>', '</li><li>', $content);

		$content = preg_replace('#\*\*(.*)\*\*#Ui', '<b>$1</b>', $content);
		$content = preg_replace('#\*(.*)\*#Ui', '<i>$1</i>', $content);
		$content = preg_replace('#\~\~(.*)\~\~#Ui', '<s>$1</s>', $content);
		$content = preg_replace('#\&i\:(.*)\;#Ui', '<span class="fa $1"></span>', $content);

		$content = trim($content);

		$content = str_replace("\r\n", "\n", $content);
		$content = str_replace("\n", '<br />', $content);

		$content = str_replace('</ul><br /> <br />', '</ul>', $content);
		$content = str_replace('</ol><br /> <br />', '</ol>', $content);
		$content = str_replace('</code> </pre><br /> <br />', '</code></pre>', $content);

		$content = preg_replace('#\<i\>\<(.*)\>\<\/i\>#Ui', '<i class="_var">&lt;$1&gt;</i>', $content);

		return self::_htmlFrom(trim($content), $full, $css);
	}

	/**
	 * @param string $content = ''
	 * @param bool $full = false
	 *
	 * @return string
	 */
	public static function ToMarkdown ($content = '', $full = false) {
		return self::_htmlTo($content, $full);
	}
}