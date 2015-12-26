<?php
namespace Quark\ViewResources\Quark\QuarkPresenceControl;

use Quark\QuarkModel;

/**
 * Interface IQuarkPresenceControlViewModel
 *
 * @package Quark\ViewResources\Quark\QuarkPresenceControl
 */
interface IQuarkPresenceControlViewModel {
	/**
	 * @return string
	 */
	public function PresenceTitle();

	/**
	 * @return string
	 */
	public function PresenceOverlaidContainer();

	/**
	 * @return string
	 */
	public function PresenceLogo();

	/**
	 * @return string
	 */
	public function PresenceMenuHeader();

	/**
	 * @param QuarkModel $user = null
	 *
	 * @return string
	 */
	public function PresenceUser(QuarkModel $user = null);
}