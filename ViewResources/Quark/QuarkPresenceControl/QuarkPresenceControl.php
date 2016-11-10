<?php
namespace Quark\ViewResources\Quark\QuarkPresenceControl;

use Quark\IQuarkCombinedViewResource;
use Quark\IQuarkViewModel;
use Quark\IQuarkViewModelWithResources;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\QuarkCombinedViewResourceBehavior;
use Quark\QuarkViewBehavior;

use Quark\ViewResources\jQuery\jQueryCore;
use Quark\ViewResources\Quark\QuarkPresence\QuarkPresence;
use Quark\ViewResources\Quark\QuarkUX\QuarkUX;

/**
 * Class QuarkPresenceControl
 *
 * @package Quark\ViewResources\Quark\QuarkPresenceControl
 */
class QuarkPresenceControl implements IQuarkCombinedViewResource, IQuarkViewResourceWithDependencies, IQuarkViewModel, IQuarkViewModelWithResources {
	use QuarkViewBehavior;
	use QuarkCombinedViewResourceBehavior;

	/**
	 * @return string
	 */
	public function LocationStylesheet () {
		return __DIR__ . '/QuarkPresenceControl.css';
	}

	/**
	 * @return string
	 */
	public function LocationController () {
		return __DIR__ . '/QuarkPresenceControl.js';
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			new jQueryCore(),
			new QuarkPresence(),
			new QuarkUX()
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
	public function ViewResources () {
		return array($this);
	}

	/**
	 * @return string
	 */
	public function TitleWidget () {
		return $this->Child() instanceof IQuarkPresenceControlViewModel
			? $this->Child()->PresenceTitle()
			: 'QuarkPresence Control';
	}

	/**
	 * @return string
	 */
	public function LogoWidget () {
		return $this->Child() instanceof IQuarkPresenceControlViewModel
			? $this->Child()->PresenceLogo()
			: 'QuarkPresence Control';
	}

	/**
	 * @return string
	 */
	public function MenuHeaderWidget () {
		return $this->Child() instanceof IQuarkPresenceControlViewModel
			? $this->Child()->PresenceMenuHeader()
			: '';
	}

	/**
	 * @return string
	 */
	public function MenuSideWidget () {
		return $this->Child() instanceof IQuarkPresenceControlViewModel
			? $this->Child()->PresenceMenuSide()
			: '';
	}

	/**
	 * @return string
	 */
	public function UserWidget () {
		return $this->Child() instanceof IQuarkPresenceControlViewModel
			? $this->Child()->PresenceUser($this->User())
			: '';
	}

	/**
	 * @return string
	 */
	public function OverlaidContainerWidget () {
		return $this->Child() instanceof IQuarkPresenceControlViewModel
			? $this->Child()->PresenceOverlaidContainer()
			: '';
	}
}