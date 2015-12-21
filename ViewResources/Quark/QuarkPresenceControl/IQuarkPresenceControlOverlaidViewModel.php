<?php
namespace Quark\ViewResources\Quark\QuarkPresenceControl;

/**
 * Interface IQuarkPresenceControlOverlaidViewModel
 *
 * @package Quark\ViewResources\Quark\QuarkPresenceControl
 */
interface IQuarkPresenceControlOverlaidViewModel {
	/**
	 * @return string
	 */
	public function PresenceOverlaidContainer();

	/**
	 * @return string
	 */
	public function PresenceOverlaidMapAPIKey();
}