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
	 * @var string[] $_weights
	 */
	private $_weights = array(
		GoogleFont::WEIGHT_THIN_100,
		GoogleFont::WEIGHT_THIN_100_ITALIC,
		GoogleFont::WEIGHT_LIGHT_300,
		GoogleFont::WEIGHT_LIGHT_300_ITALIC,
		GoogleFont::WEIGHT_REGULAR_400,
		GoogleFont::WEIGHT_REGULAR_400_ITALIC,
		GoogleFont::WEIGHT_SEMI_BOLD_600,
		GoogleFont::WEIGHT_SEMI_BOLD_600_ITALIC
	);

	/**
	 * @param string[] $weights = []
	 * @param bool $js = true
	 */
	public function __construct ($weights = [], $js = false) {
		if (func_num_args() != 0)
			$this->_weights = $weights;

		if ($js)
			$this->_js = new QuarkLocalCoreJSViewResource();
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			new FontAwesome(),
			new GoogleFont(GoogleFont::FAMILY_OPEN_SANS, $this->_weights),
			new QuarkLocalCoreCSSViewResource(),
			$this->_js
		);
	}
}