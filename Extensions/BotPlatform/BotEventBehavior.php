<?php
namespace Quark\Extensions\BotPlatform;

/**
 * Class BotEventBehavior
 *
 * @package Quark\Extensions\BotPlatform
 */
trait BotEventBehavior {
	/**
	 * @var BotPlatformActor $_actor = null
	 */
	private $_actor = null;

	/**
	 * @var string $_channel = ''
	 */
	private $_channel = null;

	/**
	 * @var string $_platform = ''
	 */
	private $_platform = '';

	/**
	 * @var IQuarkBotPlatformEventEntity[] $_entities = []
	 */
	private $_entities = array();

	/**
	 * @param BotPlatformActor $actor = null
	 *
	 * @return BotPlatformActor
	 */
	public function BotEventActor (BotPlatformActor $actor = null) {
		if (func_num_args() != 0)
			$this->_actor = $actor;

		return $this->_actor;
	}

	/**
	 * @param string $channel = ''
	 *
	 * @return string
	 */
	public function BotEventChannel ($channel = '') {
		if (func_num_args() != 0)
			$this->_channel = $channel;

		return $this->_channel;
	}

	/**
	 * @param string $platform = ''
	 *
	 * @return string
	 */
	public function BotEventPlatform ($platform = '') {
		if (func_num_args() != 0)
			$this->_platform = $platform;

		return $this->_platform;
	}

	/**
	 * @param IQuarkBotPlatformEventEntity $entity = null
	 */
	public function BotEventEntity (IQuarkBotPlatformEventEntity $entity) {
		$this->_entities[] = $entity;
	}

	/**
	 * @return IQuarkBotPlatformEventEntity[]
	 */
	public function BotEventEntities () {
		return $this->_entities;
	}

	/**
	 * @param IQuarkBotPlatformEvent|BotEventBehavior $event
	 *
	 * @return IQuarkBotPlatformEvent|BotEventBehavior
	 */
	public function BotEventReply (IQuarkBotPlatformEvent $event) {
		$event->BotEventActor($this->BotEventActor());
		$event->BotEventChannel($this->BotEventChannel());
		$event->BotEventPlatform($this->BotEventPlatform());

		return $event;
	}

	/**
	 * @param BotPlatformActor $actor = null
	 * @param string $channel = ''
	 * @param string $platform = ''
	 *
	 * @return self
	 */
	public static function BotEventIn (BotPlatformActor $actor = null, $channel = '', $platform = '') {
		$event = new self();

		$event->BotEventActor($actor);
		$event->BotEventChannel($channel);
		$event->BotEventPlatform($platform);

		return $event;
	}
}