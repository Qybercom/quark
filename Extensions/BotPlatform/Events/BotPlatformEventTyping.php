<?php
namespace Quark\Extensions\BotPlatform\Events;

use Quark\Extensions\BotPlatform\IQuarkBotPlatformEvent;

use Quark\Extensions\BotPlatform\BotPlatform;
use Quark\Extensions\BotPlatform\BotPlatformMember;
use Quark\Quark;

/**
 * Class BotPlatformEventTyping
 *
 * @package Quark\Extensions\BotPlatform\Events
 */
class BotPlatformEventTyping implements IQuarkBotPlatformEvent {
	/**
	 * @var BotPlatformMember $_actor = null
	 */
	private $_actor = null;

	/**
	 * @var string $_channel = ''
	 */
	private $_channel = '';

	/**
	 * @var int $_duration = 3
	 */
	private $_duration = 3;

	/**
	 * @var bool $_sync = true
	 */
	private $_sync = true;

	/**
	 * @var string $_platform = ''
	 */
	private $_platform = '';

	/**
	 * @param BotPlatformMember $actor = null
	 * @param string $channel = ''
	 * @param int $duration = 3
	 * @param bool $sync = true
	 * @param string $platform = ''
	 */
	public function __construct (BotPlatformMember $actor = null, $channel = '', $duration = 3, $sync = true, $platform = '') {
		$this->_actor = $actor;
		$this->_channel = $channel;
		$this->_duration = $duration;
		$this->_sync = $sync;
		$this->_platform = $platform;
	}

	/**
	 * @param BotPlatformMember $actor = null
	 *
	 * @return BotPlatformMember
	 */
	public function Actor (BotPlatformMember $actor = null) {
		if (func_num_args() != 0)
			$this->_actor = $actor;

		return $this->_actor;
	}

	/**
	 * @param string $channel = ''
	 *
	 * @return string
	 */
	public function Channel ($channel = '') {
		if (func_num_args() != 0)
			$this->_channel = $channel;

		return $this->_channel;
	}

	/**
	 * @param int $duration = 3 (seconds)
	 *
	 * @return int
	 */
	public function Duration ($duration = 3) {
		if (func_num_args() != 0)
			$this->_duration = $duration;

		return $this->_duration;
	}

	/**
	 * @param bool $sync = true
	 *
	 * @return bool
	 */
	public function Sync ($sync = true) {
		if (func_num_args() != 0)
			$this->_sync = $sync;

		return $this->_sync;
	}

	/**
	 * @param string $platform = ''
	 *
	 * @return string
	 */
	public function Platform ($platform = '') {
		if (func_num_args() != 0)
			$this->_platform = $platform;

		return $this->_platform;
	}

	/**
	 * @param BotPlatform $bot
	 * @param int $duration = 3
	 * @param callable $action = null
	 */
	public function TimeHolder (BotPlatform $bot, $duration = 3, callable $action = null) {
		if ($action == null) return;

		$this->Sync(false);
		$bot->Out($this->BotEventReply($duration));

		$action();
		sleep($duration);
	}

	/**
	 * @param int $duration = 3 (seconds)
	 *
	 * @return IQuarkBotPlatformEvent
	 */
	public function BotEventReply ($duration = 3) {
		$out = clone $this;
		$out->_duration = $duration;

		return $out;
	}

	/**
	 * @param BotPlatformMember $actor = null
	 *
	 * @return BotPlatformMember
	 */
	public function BotEventActor (BotPlatformMember $actor = null) {
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
	 * @param IQuarkBotPlatformEvent $event
	 *
	 * @return mixed
	 */
	public function BotEventTo (IQuarkBotPlatformEvent $event) {
		$event->BotEventActor($this->BotEventActor());
		$event->BotEventChannel($this->BotEventChannel());
		$event->BotEventPlatform($this->BotEventPlatform());

		return $event;
	}
}