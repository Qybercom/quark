<?php
namespace Quark\ViewResources\Quark\QuarkPresence;

use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkLocalViewResource;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\QuarkCSSViewResourceType;
use Quark\QuarkMinimizableViewResourceBehavior;

use Quark\ViewResources\Google\GoogleFont;
use Quark\ViewResources\Quark\QuarkResponsiveUI;
use Quark\ViewResources\Quark\QuarkUI;

/**
 * Class QuarkPresence
 *
 * @package Quark\ViewResources\Quark\QuarkPresence
 */
class QuarkPresence implements IQuarkSpecifiedViewResource, IQuarkLocalViewResource, IQuarkViewResourceWithDependencies {
	use QuarkMinimizableViewResourceBehavior;

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
	 */
	public function __construct ($weights = []) {
		if (func_num_args() != 0)
			$this->_weights = $weights;
	}

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
		return __DIR__ . '/QuarkPresence.css';
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			new QuarkUI($this->_weights),
			new QuarkResponsiveUI(),
			new QuarkPresenceControlsCSS()
		);
	}
}