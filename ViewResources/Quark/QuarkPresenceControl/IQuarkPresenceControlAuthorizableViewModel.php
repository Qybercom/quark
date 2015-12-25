<?php
namespace Quark\ViewResources\Quark\QuarkPresenceControl;

use Quark\QuarkModel;

/**
 * Interface IQuarkPresenceControlAuthorizableViewModel
 *
 * @package Quark\ViewResources\Quark\QuarkPresenceControl
 */
interface IQuarkPresenceControlAuthorizableViewModel {
	/**
	 * @param QuarkModel $user = null
	 *
	 * @return mixed
	 */
	public function PresenceUser(QuarkModel $user = null);
}