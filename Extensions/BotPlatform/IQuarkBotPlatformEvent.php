<?php
namespace Quark\Extensions\BotPlatform;

/**
 * Interface IQuarkBotPlatformEvent
 *
 * @package Quark\Extensions\BotPlatform
 */
interface IQuarkBotPlatformEvent {
	/**
	 * @return IQuarkBotPlatformEvent
	 */
	public function BotEventReply();

	/**
	 * @param BotPlatformMember $actor = null
	 *
	 * @return BotPlatformMember
	 */
	public function BotEventActor(BotPlatformMember $actor = null);

	/**
	 * @param string $channel = ''
	 *
	 * @return string
	 */
	public function BotEventChannel($channel = '');

	/**
	 * @param string $platform = ''
	 *
	 * @return string
	 */
	public function BotEventPlatform($platform = '');

	/**
	 * @param IQuarkBotPlatformEvent $event
	 *
	 * @return mixed
	 */
	public function BotEventTo(IQuarkBotPlatformEvent $event);
}