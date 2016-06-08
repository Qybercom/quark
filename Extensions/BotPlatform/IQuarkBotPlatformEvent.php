<?php
namespace Quark\Extensions\BotPlatform;

/**
 * Interface IQuarkBotPlatformEvent
 *
 * @package Quark\Extensions\BotPlatform
 */
interface IQuarkBotPlatformEvent {
	/**
	 * @param BotPlatformActor $actor = null
	 *
	 * @return BotPlatformActor
	 */
	public function BotEventActor(BotPlatformActor $actor = null);

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
	 * @param IQuarkBotPlatformEventEntity $entity
	 */
	public function BotEventEntity(IQuarkBotPlatformEventEntity $entity);

	/**
	 * @return IQuarkBotPlatformEventEntity[]
	 */
	public function BotEventEntities();

	/**
	 * @param IQuarkBotPlatformEvent $event
	 *
	 * @return IQuarkBotPlatformEvent
	 */
	public function BotEventReply(IQuarkBotPlatformEvent $event);

	/**
	 * @param BotPlatformActor $actor
	 * @param string $channel
	 * @param string $platform
	 *
	 * @return IQuarkBotPlatformEvent
	 */
	public static function BotEventIn(BotPlatformActor $actor, $channel, $platform);
}