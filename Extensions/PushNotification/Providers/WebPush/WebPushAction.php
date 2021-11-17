<?php
namespace Quark\Extensions\PushNotification\Providers\WebPush;

/**
 * Class WebPushAction
 *
 * @package Quark\Extensions\PushNotification\Providers\WebPush
 */
class WebPushAction {
	/**
	 * @var string[] $_properties
	 */
	private static $_properties = array(
		'Action' => 'action',
		'Title' => 'title',
		'Icon' => 'icon'
	);

	/**
	 * @var string $_action
	 */
	private $_action;

	/**
	 * @var string $_title
	 */
	private $_title;

	/**
	 * @var string $_icon
	 */
	private $_icon;

	/**
	 * @param string $action = null
	 * @param string $title = null
	 * @param string $icon = null
	 */
	public function __construct ($action = null, $title = null, $icon = null) {
		$this->Action($action);
		$this->Title($title);
		$this->Icon($icon);
	}

	/**
	 * @param string $action = null
	 *
	 * @return string
	 */
	public function Action ($action = null) {
		if (func_num_args() != 0)
			$this->_action = $action;

		return $this->_action;
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
	 * @return object
	 */
	public function Data () {
		$out = null;

		foreach (self::$_properties as $property => &$key) {
			if ($out === null) $out = array();

			$out[$key] = $this->$property;
		}

		return $out === null ? (object)$out : null;
	}
}