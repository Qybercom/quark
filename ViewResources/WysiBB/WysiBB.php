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
	 * @return array
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
}