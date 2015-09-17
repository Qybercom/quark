<?php
namespace Quark\ViewResources\WysiBB;

use Quark\IQuarkForeignViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\QuarkDTO;
use Quark\QuarkJSViewResourceType;

use Quark\ViewResources\jQuery\jQueryCore;
use Quark\ViewResources\WysiBB\CSS\DefaultTheme;

/**
 * Class WysiBB
 *
 * @package Quark\ViewResources\WysiBB
 */
class WysiBB implements IQuarkViewResource, IQuarkForeignViewResource, IQuarkViewResourceWithDependencies {
	/**
	 * @var IWysiBBTheme $_theme
	 */
	private $_theme = null;

	/**
	 * @var IWysiBBLanguage $_language
	 */
	private $_language = null;

	/**
	 * @param IWysiBBTheme    $theme
	 * @param IWysiBBLanguage $language
	 */
	public function __construct (IWysiBBTheme $theme = null, IWysiBBLanguage $language = null) {
		if ($theme == null)
			$theme = new DefaultTheme();

		$this->_theme = $theme;
		$this->_language = $language;
	}

	/**
	 * @return string
	 */
	public function Location () {
		return 'http://cdn.wysibb.com/js/jquery.wysibb.min.js';
	}

	/**
	 * @return string
	 */
	public function Type () {
		return new QuarkJSViewResourceType();
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			new jQueryCore(),
			$this->_theme,
			$this->_language
		);
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}

	/**
	 * @param string $content
	 * @param bool   $full = false
	 * @param string $css = ''
	 *
	 * @return string
	 */
	public static function ToHTML ($content = '', $full = false, $css = '') {
		$pairs = array('b', 'i', 'u', 's');
		$align = array('left', 'center', 'right');

		foreach ($pairs as $pair)
			$content = str_replace('[' . $pair . ']', '<' . $pair . '>', str_replace('[/' . $pair . ']', '</' . $pair . '>', $content));

		foreach ($align as $div)
			$content = preg_replace('#\[' . $div . '\](.*)\[\/' . $div . '\]#Uis', '<div style="text-align:' . $div . ';">$1</div>', $content);

		$content = str_replace("    ", '&nbsp;&nbsp;&nbsp;&nbsp;', str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', $content));
		$content = str_replace("\n", '<br>', str_replace("\n", '<br>', str_replace("\r\n", '<br>', $content)));

		$content = str_replace('[*]', '<li>', str_replace('[/*]', '</li>', $content));
		$content = preg_replace('#\[list\=1\](.*)\[\/list\]#Uis', '<ol>$1</ol>', $content);
		$content = str_replace('[list]', '<ul>', str_replace('[/list]', '</ul>', $content));
		$content = str_replace('[code]', '<div class="quark-document">', str_replace('[/code]', '</div>', $content));
		$content = preg_replace('#\[url\=(.*)\](.*)\[/url\]#Uis', '<a href="$1" class="quark-button">$2</a>', $content);
		$content = preg_replace('#\[img\](.*)\[/img\]#Uis', '<img src="$1" class="wysibb-image" alt="image" />', $content);
		$content = preg_replace('#\[video\](.*)\[/video\]#Uis', '<iframe src="//www.youtube.com/embed/$1" class="wysibb-video" frameborder="0" allowfullscreen></iframe>', $content);

		return $full ? '<!DOCTYPE html><html><head><title></title><style type="text/css">' . $css . '</style></head><body>' . $content . '</body></html>' : $content;
	}

	/**
	 * @param string $content
	 * @param bool   $full = false
	 *
	 * @return string
	 */
	public static function ToBB ($content = '', $full = false) {
		$pairs = array('b', 'i', 'u', 's');
		$align = array('left', 'center', 'right');

		foreach ($pairs as $pair)
			$content = str_replace('<' . $pair . '>', '[' . $pair . ']', str_replace('</' . $pair . '>', '[/' . $pair . ']', $content));

		foreach ($align as $div)
			$content = preg_replace('#\<div style\=\"text\-align\:' . $div . '\;\"\>(.*)\<\/div\>#Uis', '[' . $div . ']$1[/' . $div . ']', $content);

		$content = str_replace('<br>', "\r\n", $content);

		$content = str_replace('<li>', '[*]', str_replace('</li>', '[/*]', $content));
		$content = preg_replace('#\<ol\>(.*)\<\/ol\>#Uis', '[list=1]$1[/list]', $content);
		$content = str_replace('<ul>', '[list]', str_replace('</ul>', '[/list]', $content));
		$content = preg_replace('#<div class="quark-document">(.*)</div>#Uis', '[code]$1[/code]', $content);
		$content = str_replace('&nbsp;&nbsp;&nbsp;&nbsp;', "\t", $content);
		$content = preg_replace('#\<a href\=\"(.*)\" class\=\"quark\-button\"\>(.*)\<\/a\>#Uis', '[url=$1]$2[/url]', $content);
		$content = preg_replace('#\<img src\=\"(.*)\" class\=\"wysibb\-image\" alt\=\"image\" \/\>#Uis', '[img]$1[/img]', $content);
		$content = preg_replace('#<iframe src\=\"\/\/www\.youtube\.com\/embed\/(.*)\" class\=\"wysibb\-video\" frameborder\=\"0\" allowfullscreen\>\<\/iframe\>#Uis', '[video]$1[/video]', $content);

		return $full
			? preg_replace('#\<\!DOCTYPE html\>\<html\>\<head\>\<title\>\<\/title\>\<style type\=\"text\/css\"\>(.*)\<\/style\>\<\/head\>\<body\>(.*)\<\/body\>\<\/html\>#Uis', '$2', $content)
			: $content;
	}

	/**
	 * @param string $content
	 *
	 * @return string
	 */
	public static function Styles ($content = '') {
		return preg_replace('#\<\!DOCTYPE html\>\<html\>\<head\>\<title\>\<\/title\>\<style type\=\"text\/css\"\>(.*)\<\/style\>\<\/head\>\<body\>(.*)\<\/body\>\<\/html\>#Uis', '$1', $content);
	}
}