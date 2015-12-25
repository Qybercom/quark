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

use Quark\ViewResources\Google\GoogleMap;
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
			new QuarkPresence(),
			$this->Child() instanceof IQuarkPresenceControlOverlaidViewModel
				? new GoogleMap($this->Child()->PresenceOverlaidMapAPIKey())
				: null
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
	public function OverlaidContainerWidget () {
		return $this->Child() instanceof IQuarkPresenceControlOverlaidViewModel
			? $this->Child()->PresenceOverlaidContainer()
			: '';
	}

	/**
	 * @return string
	 */
	public function UserWidget () {
		return $this->Child() instanceof IQuarkPresenceControlAuthorizableViewModel
			? $this->Child()->PresenceUser($this->User())
			: '';
	}

	/**
	 * @return string
	 */
	public function SearchWidget () {
		return $this->Child() instanceof IQuarkPresenceControlSearchableViewModel
			? $this->Child()->PresenceSearch()
			: '';
	}

	/**
	 * @return string
	 */
	public function MenuTopWidget () {
		return $this->Child() instanceof IQuarkPresenceViewModelWithTopMenu
			? $this->Child()->PresenceMenuTop()
			: '';
	}
}