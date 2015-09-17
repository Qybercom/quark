<?php
namespace Quark\ViewResources\Quark;

use Quark\IQuarkLocalViewResource;
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
class QuarkUI implements IQuarkViewResource, IQuarkLocalViewResource, IQuarkViewResourceWithDependencies {
	private $_js = null;
	private $_sizes = array(
		GoogleFont::N300,
		GoogleFont::N600,
		GoogleFont::I300,
		GoogleFont::I600
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
			new GoogleFont('Open Sans', array(
				GoogleFont::OPTION_SIZES => $this->_sizes
			)),
			new QuarkLocalCoreCSSViewResource(),
			$this->_js
		);
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
	public function Type () {
		// TODO: Implement Type() method.
	}

	/**
	 * @return bool
	 */
	public function CacheControl () {
		// TODO: Implement CacheControl() method.
	}
}