<?php
namespace Quark\ViewResources\Quark;

use Quark\IQuarkLocalViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\QuarkLocalCoreCSSViewResource;
use Quark\QuarkLocalCoreJSViewResource;

use Quark\ViewResources\Google\Font;
use Quark\ViewResources\FontAwesome\FontAwesome;

/**
 * Class QuarkUI
 *
 * @package Quark\ViewResources\Quark
 */
class QuarkUI implements IQuarkViewResource, IQuarkLocalViewResource, IQuarkViewResourceWithDependencies {
	private $_js = null;
	private $_sizes = array(
		Font::N300,
		Font::N600,
		Font::I300,
		Font::I600
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
	 * @return array
	 */
	public function Dependencies () {
		return array(
			new FontAwesome(),
			new Font('Open Sans', array(
				Font::OPTION_SIZES => $this->_sizes
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