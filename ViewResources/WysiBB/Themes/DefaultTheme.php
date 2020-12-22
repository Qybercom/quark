<?php
namespace Quark\ViewResources\WysiBB\Themes;

use Quark\QuarkCSSViewResourceType;
use Quark\ViewResources\WysiBB\IWysiBBTheme;

/**
 * Class DefaultTheme
 *
 * @package Quark\ViewResources\WysiBB\Themes
 */
class DefaultTheme implements IWysiBBTheme {
	/**
	 * @return string
	 */
	public function Location () {
		return 'https://cdn.wysibb.com/css/default/wbbtheme.css';
	}

	/**
	 * @return string
	 */
	public function Type () {
		return new QuarkCSSViewResourceType();
	}
}