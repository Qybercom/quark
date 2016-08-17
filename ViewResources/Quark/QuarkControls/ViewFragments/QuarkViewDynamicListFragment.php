<?php
namespace Quark\ViewResources\Quark\QuarkControls\ViewFragments;

use Quark\IQuarkViewFragment;

/**
 * Class QuarkViewDynamicListFragment
 *
 * @package Quark\ViewResources\Quark\QuarkControls\ViewFragments
 */
class QuarkViewDynamicListFragment implements IQuarkViewFragment {
	const NAME = 'list';
	const PARAM_NAME = '{name}';
	const PARAM_VALUE = '{value}';
	const PARAM_PLACEHOLDER = '{placeholder}';

	/**
	 * @var string[] $_items = []
	 */
	private $_items = array();

	/**
	 * @var string $_name = self::NAME
	 */
	private $_name = self::NAME;

	/**
	 * @var string $_placeholder = ''
	 */
	private $_placeholder = '';

	/**
	 * @var string $_template = ''
	 */
	private $_template = '';

	/**
	 * @param string $name = self::NAME
	 * @param string $placeholder = ''
	 * @param string[] $items = []
	 * @param string $template = ''
	 */
	public function __construct ($name = self::NAME, $placeholder = '', $items = [], $template = '') {
		$this->_name = $name;
		$this->_placeholder = $placeholder;
		$this->_items = $items;
		$this->_template = func_num_args() == 4
			? $template
			: 	'<div class="quark-list-item">' .
					'<input class="quark-input item-value" name="' . self::PARAM_NAME . '[]" value="' . self::PARAM_VALUE . '" placeholder="' . self::PARAM_PLACEHOLDER . '" />' .
					'<a class="quark-button fa fa-times item-remove"></a>' .
				'</div>';
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
	 * @param string $placeholder = ''
	 *
	 * @return string
	 */
	public function Placeholder ($placeholder = '') {
		if (func_num_args() != 0)
			$this->_placeholder = $placeholder;

		return $this->_placeholder;
	}

	/**
	 * @param string[] $items = []
	 *
	 * @return string[]
	 */
	public function Items ($items = []) {
		if (func_num_args() != 0)
			$this->_items = $items;

		return $this->_items;
	}

	/**
	 * @param string $item = ''
	 *
	 * @return QuarkViewDynamicListFragment
	 */
	public function Item ($item = '') {
		if (func_num_args() != 0)
			$this->_items[] = $item;

		return $this;
	}

	/**
	 * @param string $template = ''
	 *
	 * @return string
	 */
	public function Template ($template = '') {
		if (func_num_args() != 0)
			$this->_template = $template;

		return $this->_template;
	}

	/**
	 * @return string
	 */
	public function CompileFragment () {
		$out = '';

		foreach ($this->_items as $item)
			$out .=
				str_replace(self::PARAM_PLACEHOLDER, $this->_placeholder,
				str_replace(self::PARAM_VALUE, $item,
				str_replace(self::PARAM_NAME, $this->_name,
				$this->_template
			)));

		return $out;
	}
}