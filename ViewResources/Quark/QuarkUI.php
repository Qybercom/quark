<?php
namespace Quark\ViewResources\Quark;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\QuarkLocalCoreCSSViewResource;
use Quark\QuarkLocalCoreJSViewResource;

use Quark\ViewResources\Google\GoogleFont;
use Quark\ViewResources\FontAwesome\FontAwesome;

/**
 * Class QuarkUI
 *
 * @package Quark\ViewResources\Quark
 */
class QuarkUI implements IQuarkViewResource, IQuarkViewResourceWithDependencies {
	/**
	 * @var QuarkLocalCoreJSViewResource $_js
	 */
	private $_js = null;

	/**
	 * @var string[] $_sizes
	 */
	private $_sizes = array(
		GoogleFont::WEIGHT_LIGHT_300,
		GoogleFont::WEIGHT_LIGHT_300_ITALIC,
		GoogleFont::WEIGHT_SEMI_BOLD_600,
		GoogleFont::WEIGHT_SEMI_BOLD_600_ITALIC
	);

	/**
	 * @param array $sizes
	 * @param bool  $js
	 */
	public function __construct ($sizes = [], $js = true) {
		if (func_num_args() != 0)
			$this->_sizes = $sizes;

		if ($js)
			$this->_js = new QuarkLocalCoreJSViewResource();
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			new FontAwesome(),
			new GoogleFont(GoogleFont::FAMILY_OPEN_SANS, $this->_sizes),
			new QuarkLocalCoreCSSViewResource(),
			$this->_js
		);
	}
}