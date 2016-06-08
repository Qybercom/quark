<?php
namespace Quark\Extensions\BotPlatform\Entities;

use Quark\Extensions\BotPlatform\IQuarkBotPlatformEventEntity;

/**
 * Class BotPlatformEventEntityKeyboardButton
 *
 * @package Quark\Extensions\BotPlatform\Entities
 */
class BotPlatformEventEntityKeyboardButton implements IQuarkBotPlatformEventEntity {
	/**
	 * @var string $_action = ''
	 */
	private $_action = '';

	/**
	 * @var string $_icon = ''
	 */
	private $_icon = '';

	/**
	 * @param string $action = ''
	 * @param string $icon = ''
	 */
	public function __construct ($action = '',  $icon = '') {
		$this->Action($action);
		$this->Icon($icon);
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
	 * @return string
	 */
	public function BotEntityFallbackContent () {
		// TODO: Implement BotEntityFallbackContent() method.
	}
}