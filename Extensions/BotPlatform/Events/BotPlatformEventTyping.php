<?php
namespace Quark\Extensions\BotPlatform\Events;

use Quark\Extensions\BotPlatform\IQuarkBotPlatformEvent;

use Quark\Extensions\BotPlatform\BotPlatform;
use Quark\Extensions\BotPlatform\BotEventBehavior;

/**
 * Class BotPlatformEventTyping
 *
 * @package Quark\Extensions\BotPlatform\Events
 */
class BotPlatformEventTyping implements IQuarkBotPlatformEvent {
	use BotEventBehavior;

	/**
	 * @var int $_duration = 3
	 */
	private $_duration = 3;

	/**
	 * @var bool $_sync = true
	 */
	private $_sync = true;

	/**
	 * @param int $duration = 3
	 * @param bool $sync = true
	 */
	public function __construct ($duration = 3, $sync = true) {
		$this->Duration($duration);
		$this->Sync($sync);
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
	 * @param BotPlatform $bot
	 * @param IQuarkBotPlatformEvent $event
	 * @param int $duration = 3
	 * @param callable $action = null
	 */
	public static function TimeHolder (BotPlatform $bot, IQuarkBotPlatformEvent $event, $duration = 3, callable $action = null) {
		if ($action == null) return;

		$bot->Out($event->BotEventReply(new self($duration, false)));

		$action();
		sleep($duration);
	}

	/**
	 * @param int $duration = 3 (seconds)
	 *
	 * @return IQuarkBotPlatformEvent
	 */
	public function BotEventReply1 ($duration = 3) {
		$out = clone $this;
		$out->_duration = $duration;

		return $out;
	}
}