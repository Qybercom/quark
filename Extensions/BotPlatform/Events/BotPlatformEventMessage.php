<?php
namespace Quark\Extensions\BotPlatform\Events;

use Quark\QuarkDate;

use Quark\Extensions\BotPlatform\IQuarkBotPlatformEvent;

use Quark\Extensions\BotPlatform\BotEventBehavior;

/**
 * Class BotPlatformEventMessage
 *
 * @package Quark\Extensions\BotPlatform\Events
 */
class BotPlatformEventMessage implements IQuarkBotPlatformEvent {
	const TYPE_TEXT = 'type.text';
	const TYPE_IMAGE = 'type.image';
	const TYPE_STICKER = 'type.sticker';
	const TYPE_CUSTOM = '__custom__';

	use BotEventBehavior;

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
	 * @var QuarkDate $_sent = null
	 */
	private $_sent = null;

	/**
	 * @param string $payload = ''
	 * @param string $type = self::TYPE_TEXT
	 */
	public function __construct ($payload = '', $type = self::TYPE_TEXT) {
		$this->Payload($payload);
		$this->Type($type);
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
	 * @param string $pattern = ''
	 * @param array &$matches = []
	 *
	 * @return int
	 */
	public function Match ($pattern = '', &$matches = []) {
		return preg_match_all($pattern, $this->_payload, $matches, PREG_SET_ORDER);
	}
}