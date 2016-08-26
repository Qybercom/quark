<?php
namespace Quark\ViewResources\Quark\QuarkPresenceControl;

use Quark\QuarkKeyValuePair;

/**
 * Interface IQuarkPresenceControlViewModelWithBreadcrumbs
 *
 * @package Quark\ViewResources\Quark\QuarkPresenceControl
 */
interface IQuarkPresenceControlViewModelWithBreadcrumbs extends IQuarkPresenceControlViewModel {
	/**
	 * @return QuarkKeyValuePair[]
	 */
	public function PresenceBreadcrumbs();
}