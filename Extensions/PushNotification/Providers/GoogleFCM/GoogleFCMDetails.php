<?php
namespace Quark\Extensions\PushNotification\Providers\GoogleFCM;

use Quark\Extensions\PushNotification\IQuarkPushNotificationDetails;

use Quark\Extensions\PushNotification\Providers\AppleAPNS\AppleAPNSDetails;

/**
 * Class GoogleFCMDetails
 *
 * @package Quark\Extensions\PushNotification\Providers\GoogleFCM
 */
class GoogleFCMDetails implements IQuarkPushNotificationDetails {
	/**
	 * @var string $_title = null
	 */
	private $_title;

	/**
	 * @var string $_body = null
	 */
	private $_body = null;

	/**
	 * @var string $_icon = null
	 */
	private $_icon = null;

	/**
	 * @var int $_badge = 1
	 */
	private $_badge = 1;

	/**
	 * @var string $_sound = AppleAPNSDetails::SOUND_DEFAULT
	 */
	private $_sound = AppleAPNSDetails::SOUND_DEFAULT;

	/**
	 * @var string $_tag = null
	 */
	private $_tag = null;

	/**
	 * @var string $_color = null
	 */
	private $_color = null;

	/**
	 * @var string $_clickAction = null
	 */
	private $_clickAction = null;

	/**
	 * @var string $_bodyLocKey = null
	 */
	private $_bodyLocKey = null;

	/**
	 * @var string $_bodyLocArgs = null
	 */
	private $_bodyLocArgs = null;

	/**
	 * @var string $_titleLocKey = null
	 */
	private $_titleLocKey = null;

	/**
	 * @var string $_titleLocArgs = null
	 */
	private $_titleLocArgs = null;

	/**
	 * @var string[] $_changes = []
	 */
	private $_changes = array();

	/**
	 * @param string $title = null
	 * @param string $body = null
	 * @param string $icon = null
	 */
	public function __construct ($title = null, $body = null, $icon = null) {
		$args = func_num_args();

		if ($args > 0) $this->Title($title);
		if ($args > 1) $this->Body($body);
		if ($args > 2) $this->Icon($icon);
	}

	/**
	 * @return string[]
	 */
	public function Changes () {
		return $this->_changes;
	}

	/**
	 * @param string $key
	 * @param $value
	 */
	private function _change ($key, $value) {
		$this->$key = $value;

		if (!in_array($key, $this->_changes))
			$this->_changes[] = $key;
	}

	/**
	 * @param string $title = null
	 *
	 * @return string
	 */
	public function Title ($title = null) {
		if (func_num_args() != 0)
			$this->_change('_title', $title);

		return $this->_title;
	}

	/**
	 * @param string $body = null
	 *
	 * @return string
	 */
	public function Body ($body = null) {
		if (func_num_args() != 0)
			$this->_change('_body', $body);

		return $this->_body;
	}

	/**
	 * @param string $icon = null
	 *
	 * @return string
	 */
	public function Icon ($icon = null) {
		if (func_num_args() != 0)
			$this->_change('_icon', $icon);

		return $this->_icon;
	}

	/**
	 * @param int $badge = 1
	 *
	 * @return int
	 */
	public function Badge ($badge = 1) {
		if (func_num_args() != 0)
			$this->_change('_badge', $badge);

		return $this->_badge;
	}

	/**
	 * @param string $sound = AppleAPNSDetails::SOUND_DEFAULT
	 *
	 * @return string
	 */
	public function Sound ($sound = AppleAPNSDetails::SOUND_DEFAULT) {
		if (func_num_args() != 0)
			$this->_change('_sound', $sound);

		return $this->_sound;
	}

	/**
	 * @param string $tag = null
	 *
	 * @return string
	 */
	public function Tag ($tag = null) {
		if (func_num_args() != 0)
			$this->_change('_tag', $tag);

		return $this->_tag;
	}

	/**
	 * @param string $color = null
	 *
	 * @return string
	 */
	public function Color ($color = null) {
		if (func_num_args() != 0)
			$this->_change('_color', $color);

		return $this->_color;
	}

	/**
	 * @param string $action = null
	 *
	 * @return string
	 */
	public function ClickAction ($action = null) {
		if (func_num_args() != 0)
			$this->_change('_clickAction', $action);

		return $this->_clickAction;
	}

	/**
	 * @param string $key = null
	 *
	 * @return string
	 */
	public function BodyLocKey ($key = null) {
		if (func_num_args() != 0)
			$this->_change('_bodyLocKey', $key);

		return $this->_bodyLocKey;
	}

	/**
	 * @param string $args = null
	 *
	 * @return string
	 */
	public function BodyLocArgs ($args = null) {
		if (func_num_args() != 0)
			$this->_change('_bodyLocArgs', $args);

		return $this->_bodyLocArgs;
	}

	/**
	 * @param string $key = null
	 *
	 * @return string
	 */
	public function TitleLocKey ($key = null) {
		if (func_num_args() != 0)
			$this->_change('_titleLocKey', $key);

		return $this->_titleLocKey;
	}

	/**
	 * @param string $args = null
	 *
	 * @return string
	 */
	public function TitleLocArgs ($args = null) {
		if (func_num_args() != 0)
			$this->_change('_titleLocArgs', $args);

		return $this->_titleLocArgs;
	}

	/**
	 * @return string
	 */
	public function PNProviderType () {
		return GoogleFCM::TYPE;
	}

	/**
	 * @param object|array $payload
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function PNDetails ($payload, $options) {
		$out = array();

		foreach ($this->_changes as $key)
			$out[$key] = $this->$key;

		return sizeof($out) == 0 ? null : $out;
	}
}