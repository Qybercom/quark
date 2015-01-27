<?php
namespace Quark\ViewResources\WysiBB\JS;

use Quark\QuarkJSViewResourceType;
use Quark\ViewResources\WysiBB\IWysiBBLanguage;

/**
 * Class LanguageRU
 *
 * @package Quark\ViewResources\WysiBB\JS
 */
class LanguageRU implements IWysiBBLanguage {
	/**
	 * @return string
	 */
	public function Location () {
		return __DIR__ . '/ru.js';
	}

	/**
	 * @return string
	 */
	public function Type () {
		return new QuarkJSViewResourceType();
	}

	/**
	 * @return bool
	 */
	public function CacheControl () {
		// TODO: Implement CacheControl() method.
	}
}