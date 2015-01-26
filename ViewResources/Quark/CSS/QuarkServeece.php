<?php
namespace Quark\ViewResources\Quark\CSS;

use Quark\IQuarkViewResource;
use Quark\IQuarkLocalViewResource;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\QuarkCSSViewResourceType;
use Quark\QuarkLocalCoreCSSViewResource;

use Quark\ViewResources\FontAwesome\FontAwesome;
use Quark\ViewResources\Google\Font;

/**
 * Class QuarkServeece
 *
 * @package Quark\ViewResources\Quark\CSS
 */
class QuarkServeece implements IQuarkViewResource, IQuarkLocalViewResource, IQuarkViewResourceWithDependencies {
	/**
	 * @return string
	 */
	public function Type () {
		return new QuarkCSSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return __DIR__ . '/QuarkServeece.css';
	}

	/**
	 * @return bool
	 */
	public function CacheControl () {
		return true;
	}

	/**
	 * @return array
	 */
	public function Dependencies () {
		return array(
			new FontAwesome(),
			new Font('Open Sans', array(
				Font::OPTION_SIZES => array(Font::N300)
			)),
			new QuarkLocalCoreCSSViewResource()
		);
	}
}