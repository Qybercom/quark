<?php
namespace Quark\ViewResources\WysiBB\CSS;

use Quark\QuarkCSSViewResourceType;
use Quark\ViewResources\WysiBB\IWysiBBTheme;

/**
 * Class DefaultTheme
 *
 * @package Quark\ViewResources\WysiBB\CSS
 */
class DefaultTheme implements IWysiBBTheme {
	/**
	 * @return string
	 */
	public function Location () {
		return 'http://cdn.wysibb.com/css/default/wbbtheme.css';
	}

	/**
	 * @return string
	 */
	public function Type () {
		return new QuarkCSSViewResourceType();
	}
}