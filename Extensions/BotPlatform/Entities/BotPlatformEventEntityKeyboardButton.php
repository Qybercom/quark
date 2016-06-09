<?php
namespace Quark\Extensions\BotPlatform\Entities;

use Quark\Extensions\BotPlatform\IQuarkBotPlatformEventEntity;

/**
 * Class BotPlatformEventEntityKeyboardButton
 *
 * @package Quark\Extensions\BotPlatform\Entities
 */
class BotPlatformEventEntityKeyboardButton implements IQuarkBotPlatformEventEntity {
	const ENTITY = 'entity.keyboard_button';

	const TYPE_DEFAULT = 'default';
	const TYPE_PRIMARY = 'primary';
	const TYPE_SUCCESS = 'success';
	const TYPE_WARNING = 'warning';
	const TYPE_DANGER = 'danger';

	/**
	 * @var string $_action = ''
	 */
	private $_action = '';

	/**
	 * @var string $_icon = ''
	 */
	private $_icon = '';

	/**
	 * @var string $_type = ''
	 */
	private $_type = '';

	/**
	 * @var string $_link = ''
	 */
	private $_link = '';

	/**
	 * @param string $action = ''
	 * @param string $icon = ''
	 * @param string $type = self::TYPE_DEFAULT
	 * @param $link = ''
	 */
	public function __construct ($action = '',  $icon = '', $type = self::TYPE_DEFAULT, $link = '') {
		$this->Action($action);
		$this->Icon($icon);
		$this->Type($type);
		$this->Link($link);
	}

	/**
	 * @param string $action = ''
	 *
	 * @return string
	 */
	public function Action ($action = '') {
		if (func_num_args() != 0)
			$this->_action = $action;

		return $this->_action;
	}

	/**
	 * @param string $icon = ''
	 *
	 * @return string
	 */
	public function Icon ($icon = '') {
		if (func_num_args() != 0)
			$this->_icon = $icon;

		return $this->_icon;
	}

	/**
	 * @param string $type = self::TYPE_PRIMARY
	 *
	 * @return string
	 */
	public function Type ($type = '') {
		if (func_num_args() != 0)
			$this->_type = $type;

		return $this->_type;
	}

	/**
	 * @param string $link = ''
	 *
	 * @return string
	 */
	public function Link ($link = '') {
		if (func_num_args() != 0)
			$this->_link = $link;

		return $this->_link;
	}

	/**
	 * @return string
	 */
	public function BotEntityType () {
		return self::ENTITY;
	}

	/**
	 * @return string
	 */
	public function BotEntityFallbackContent () {
		// TODO: Implement BotEntityFallbackContent() method.
	}
}