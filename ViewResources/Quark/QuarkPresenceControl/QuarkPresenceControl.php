<?php
namespace Quark\ViewResources\Quark\QuarkPresenceControl;

use Quark\IQuarkLocalViewResource;
use Quark\IQuarkViewModel;
use Quark\IQuarkViewModelWithResources;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\QuarkCSSViewResourceType;

use Quark\ViewResources\ChartJS\ChartJS;
use Quark\ViewResources\Quark\CSS\QuarkPresence;

/**
 * Class QuarkPresenceControl
 *
 * @package Quark\ViewResources\Quark\QuarkPresenceControl
 */
class QuarkPresenceControl implements IQuarkViewResource, IQuarkLocalViewResource, IQuarkViewResourceWithDependencies, IQuarkViewModel, IQuarkViewModelWithResources {
	/**
	 * @return IQuarkViewResourceType
	 */
	public function Type () {
		return new QuarkCSSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return __DIR__ . '/QuarkPresenceControl.css';
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			new QuarkPresence()
		);
	}

	/**
	 * @return bool
	 */
	public function CacheControl () {
		return true;
	}

	/**
	 * @return string
	 */
	public function View () {
		return __DIR__ . '/QuarkPresenceControlLayout.php';
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Resources () {
		return array($this);
	}
}