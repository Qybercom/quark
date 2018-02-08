<?php
namespace Quark\Extensions\Feedly\Components;

/**
 * Class FeedlyCategory
 *
 * @package Quark\Extensions\Feedly\Components
 */
class FeedlyCategory {
	const MUST = 'global.must';
	const ALL = 'global.all';
	const UNCATEGORIZED = 'global.uncategorized';

	/**
	 * @var string $_id = ''
	 */
	private $_id = '';

	/**
	 * @var string $_label = ''
	 */
	private $_label = '';

	/**
	 * @param string $id = ''
	 * @param string $label = ''
	 */
	public function __construct ($id = '', $label = '') {
		$this->ID($id);
		$this->Label($label);
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
	 * @param string $label = ''
	 *
	 * @return string
	 */
	public function Label ($label = '') {
		if (func_num_args() != 0)
			$this->_label = $label;

		return $this->_label;
	}
}