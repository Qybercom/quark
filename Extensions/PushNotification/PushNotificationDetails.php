<?php
namespace Quark\Extensions\PushNotification;

/**
 * Class PushNotificationDetails
 *
 * @package Quark\Extensions\PushNotification
 */
class PushNotificationDetails {
	/**
	 * @var string $_title
	 */
	private $_title;

	/**
	 * @var string $_subtitle
	 */
	private $_subtitle;

	/**
	 * @var string $_body
	 */
	private $_body;

	/**
	 * @var string $_icon
	 */
	private $_icon;

	/**
	 * @var int $_badge
	 */
	private $_badge;

	/**
	 * @var string $_sound
	 */
	private $_sound;

	/**
	 * @param string $title = null
	 * @param string $body = null
	 * @param string $icon = null
	 */
	public function __construct ($title = null, $body = null, $icon = null) {
		$this->Title($title);
		$this->Body($body);
		$this->Icon($icon);
	}

	/**
	 * @param string $title = null
	 *
	 * @return string
	 */
	public function Title ($title = null) {
		if (func_num_args() != 0)
			$this->_title = $title;

		return $this->_title;
	}

	/**
	 * @param string $subtitle = null
	 *
	 * @return string
	 */
	public function Subitle ($subtitle = null) {
		if (func_num_args() != 0)
			$this->_subtitle = $subtitle;

		return $this->_subtitle;
	}

	/**
	 * @param string $body = null
	 *
	 * @return string
	 */
	public function Body ($body = null) {
		if (func_num_args() != 0)
			$this->_body = $body;

		return $this->_body;
	}

	/**
	 * @param string $icon = null
	 *
	 * @return string
	 */
	public function Icon ($icon = null) {
		if (func_num_args() != 0)
			$this->_icon = $icon;

		return $this->_icon;
	}

	/**
	 * @param int $badge = null
	 *
	 * @return int
	 */
	public function Badge ($badge = null) {
		if (func_num_args() != 0)
			$this->_badge = $badge;

		return $this->_badge;
	}

	/**
	 * @param string $sound = null
	 *
	 * @return string
	 */
	public function Sound ($sound = null) {
		if (func_num_args() != 0)
			$this->_sound = $sound;

		return $this->_sound;
	}

	/**
	 * @param string $message = ''
	 *
	 * @return PushNotificationDetails
	 */
	public static function Instant ($message = '') {
		return new self(null, $message);
	}
}