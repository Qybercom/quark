<?php
namespace Quark\ViewResources\Quark\QuarkPresenceControl;

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
	public function PresenceLogo();
}