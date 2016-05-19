<?php
namespace Quark\Extensions\BotPlatform;

use Quark\QuarkDate;

/**
 * Class BotPlatformMessage
 *
 * @package Quark\Extensions\BotPlatform
 */
class BotPlatformMessage {
	const TYPE_TEXT = 'type.text';

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
	 * @var BotPlatformMember $_author = null
	 */
	private $_author = null;

	/**
	 * @var QuarkDate $_sent = null
	 */
	private $_sent = null;

	/**
	 * @var string $_channel = ''
	 */
	private $_channel = '';

	/**
	 * @var string $_platform = ''
	 */
	private $_platform = '';

	/**
	 * @param $payload = ''
	 * @param string $id = ''
	 * @param string $type = ''
	 * @param BotPlatformMember $author = null
	 * @param QuarkDate $sent = null
	 * @param string $channel = ''
	 * @param string $platform = ''
	 */
	public function __construct ($payload = '', $id = '', $type = '', BotPlatformMember $author = null, QuarkDate $sent = null, $channel = '', $platform = '') {
		$this->_payload = $payload;
		$this->_id = $id;
		$this->_type = $type;
		$this->_author = $author;
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
	 * @param BotPlatformMember $author = null
	 *
	 * @return BotPlatformMember
	 */
	public function Author (BotPlatformMember $author = null) {
		if (func_num_args() != 0)
			$this->_author = $author;

		return $this->_author;
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
	 * @param string $type = ''
	 * @param string $payload = ''
	 *
	 * @return BotPlatformMessage
	 */
	public function Reply ($type = '', $payload = '') {
		$out = clone $this;

		$out->Type($type);
		$out->Payload($payload);

		return $out;
	}
}