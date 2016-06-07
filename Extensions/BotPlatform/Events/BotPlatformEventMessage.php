<?php
namespace Quark\Extensions\BotPlatform\Events;

use Quark\QuarkDate;

use Quark\Extensions\BotPlatform\IQuarkBotPlatformEvent;

use Quark\Extensions\BotPlatform\BotPlatformMember;

/**
 * Class BotPlatformEventMessage
 *
 * @package Quark\Extensions\BotPlatform\Events
 */
class BotPlatformEventMessage implements IQuarkBotPlatformEvent {
	const TYPE_TEXT = 'type.text';
	const TYPE_IMAGE = 'type.image';
	const TYPE_STICKER = 'type.sticker';

	/**
	 * @var $_payload = ''
	 */
	private $_payload = '';

	/**
	 * @var string $_id = ''
	 */
	private $_id = '';

	/**
	 * @var string $_type = ''
	 */
	private $_type = '';

	/**
	 * @var BotPlatformMember $_actor = null
	 */
	private $_actor = null;

	/**
	 * @var QuarkDate $_sent = null
	 */
	private $_sent = null;

	/**
	 * @var string $_channel = ''
	 */
	private $_channel = null;

	/**
	 * @var string $_platform = ''
	 */
	private $_platform = '';

	/**
	 * @param $payload = ''
	 * @param string $id = ''
	 * @param string $type = ''
	 * @param BotPlatformMember $actor = null
	 * @param QuarkDate $sent = null
	 * @param string $channel = ''
	 * @param string $platform = ''
	 */
	public function __construct ($payload = '', $id = '', $type = '', BotPlatformMember $actor = null, QuarkDate $sent = null, $channel = '', $platform = '') {
		$this->_payload = $payload;
		$this->_id = $id;
		$this->_type = $type;
		$this->_actor = $actor;
		$this->_sent = $sent;
		$this->_channel = $channel;
		$this->_platform = $platform;
	}

	/**
	 * @param $payload = ''
	 *
	 * @return mixed
	 */
	public function Payload ($payload = '') {
		if (func_num_args() != 0)
			$this->_payload = $payload;

		return $this->_payload;
	}

	/**
	 * @param string $id = ''
	 *
	 * @return string
	 */
	public function ID ($id = '') {
		if (func_num_args() != 0)
			$this->_id = $id;

		return $this->_id;
	}

	/**
	 * @param string $type = ''
	 *
	 * @return string
	 */
	public function Type ($type = '') {
		if (func_num_args() != 0)
			$this->_type = $type;

		return $this->_type;
	}

	/**
	 * @param string $pattern = ''
	 * @param array &$matches = []
	 *
	 * @return int
	 */
	public function Match ($pattern = '', &$matches = []) {
		return preg_match_all($pattern, $this->_payload, $matches, PREG_SET_ORDER);
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
	 * @param QuarkDate $sent = null
	 *
	 * @return QuarkDate
	 */
	public function Sent (QuarkDate $sent = null) {
		if (func_num_args() != 0)
			$this->_sent = $sent;

		return $this->_sent;
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
	 * @param string $payload = ''
	 * @param string $type = ''
	 *
	 * @return IQuarkBotPlatformEvent
	 */
	public function BotEventReply ($payload = '', $type = self::TYPE_TEXT) {
		$out = clone $this;

		$out->Payload($payload);
		$out->Type($type);

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