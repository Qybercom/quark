<?php
namespace Quark\ViewResources\Quark\QuarkPresenceControl;

use Quark\IQuarkLocalViewResource;
use Quark\IQuarkViewModel;
use Quark\IQuarkViewModelWithResources;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\QuarkCSSViewResourceType;

use Quark\QuarkViewBehavior;

use Quark\ViewResources\Quark\CSS\QuarkPresence;

/**
 * Class QuarkPresenceControl
 *
 * @package Quark\ViewResources\Quark\QuarkPresenceControl
 */
class QuarkPresenceControl implements IQuarkViewResource, IQuarkLocalViewResource, IQuarkViewResourceWithDependencies, IQuarkViewModel, IQuarkViewModelWithResources {
	use QuarkViewBehavior;

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