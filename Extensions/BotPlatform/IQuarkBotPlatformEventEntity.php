<?php
namespace Quark\Extensions\BotPlatform;

/**
 * Interface IQuarkBotPlatformEventEntity
 *
 * @package Quark\Extensions\BotPlatform
 */
interface IQuarkBotPlatformEventEntity {
	/**
	 * @return string
	 */
	public function BotEntityType();

	/**
	 * @return mixed
	 */
	public function BotEntityContent();

	/**
	 * @return string
	 */
	public function BotEntityFallbackContent();
}