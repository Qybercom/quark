<?php
namespace Quark\ViewResources\WysiBB\Languages;

use Quark\QuarkJSViewResourceType;
use Quark\ViewResources\WysiBB\IWysiBBLanguage;

/**
 * Class LanguageRU
 *
 * @package Quark\ViewResources\WysiBB\Languages
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
	public function Minimize () {
		return true;
	}
}