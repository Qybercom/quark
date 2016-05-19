<?php
namespace Quark\Extensions\BotPlatform;

use Quark\IQuarkLinkedModel;
use Quark\IQuarkModel;
use Quark\IQuarkStrongModel;

/**
 * Class BotPlatformMember
 *
 * @package Quark\Extensions\BotPlatform
 */
class BotPlatformMember implements IQuarkModel, IQuarkStrongModel, IQuarkLinkedModel {
	/**
	 * @var string $_id = ''
	 */
	private $_id = '';

	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @param string $id = ''
	 * @param string $name = ''
	 */
	public function __construct ($id, $name) {
		$this->_id = $id;
		$this->_name = $name;
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
	 * @param string $name = ''
	 *
	 * @return string
	 */
	public function Name ($name = '') {
		if (func_num_args() != 0)
			$this->_name = $name;

		return $this->_name;
	}

	/**
	 * @return mixed
	 */
	public function Fields () {
		// TODO: Implement Fields() method.
	}

	/**
	 * @return mixed
	 */
	public function Rules () {
		// TODO: Implement Rules() method.
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Link ($raw) {
		// TODO: Implement Link() method.
	}

	/**
	 * @return mixed
	 */
	public function Unlink () {
		// TODO: Implement Unlink() method.
	}
}