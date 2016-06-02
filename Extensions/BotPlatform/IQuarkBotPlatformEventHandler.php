<?php
namespace Quark\Extensions\BotPlatform;

/**
 * Interface IQuarkBotPlatformEventHandler
 *
 * @package Quark\Extensions\BotPlatform
 */
interface IQuarkBotPlatformEventHandler {
	/**
	 * @param BotPlatform $bot
	 * @param IQuarkBotPlatformEvent $event
	 *
	 * @return mixed
	 */
	public function BotEvent(BotPlatform $bot, IQuarkBotPlatformEvent $event);
}