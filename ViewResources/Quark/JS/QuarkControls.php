<?php
namespace Quark\ViewResources\Quark\JS;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceWithDependencies;
use Quark\IQuarkLocalViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkViewFragment;

use Quark\QuarkJSViewResourceType;
use Quark\QuarkLocalCoreJSViewResource;

use Quark\ViewResources\jQuery\jQueryCore;

/**
 * Class QuarkControls
 *
 * @package Quark\ViewResources\Quark\JS
 */
class QuarkControls implements IQuarkViewResource, IQuarkLocalViewResource, IQuarkViewResourceWithDependencies {
	/**
	 * @return IQuarkViewResourceType
	 */
	public function Type () {
		return new QuarkJSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return __DIR__ . '/QuarkControls.js';
	}

	/**
	 * @return bool
	 */
	public function CacheControl () {
		return true;
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			new jQueryCore(),
			new QuarkLocalCoreJSViewResource(),
			new QuarkUX()
		);
	}
}

/**
 * Class QuarkViewDialogFragment
 *
 * @package Quark\ViewResources\Quark\JS
 */
class QuarkViewDialogFragment implements IQuarkViewFragment {
	const MESSAGE_WAIT = 'Please wait...';
	const MESSAGE_SUCCESS = 'Operation succeeded';
	const MESSAGE_ERROR = 'Operation failed';
	const ACTION_CONFIRM = 'Confirm';
	const ACTION_CLOSE = 'Close';

	/**
	 * @var string $_id = ''
	 */
	private $_id = '';

	/**
	 * @var string $_header = ''
	 */
	private $_header = '';

	/**
	 * @var string $_content = ''
	 */
	private $_content = '';

	/**
	 * @var string $_messageWait = self::MESSAGE_WAIT
	 */
	private $_messageWait = self::MESSAGE_WAIT;

	/**
	 * @var string $_messageSuccess = self::MESSAGE_SUCCESS
	 */
	private $_messageSuccess = self::MESSAGE_SUCCESS;

	/**
	 * @var string $_messageError = self::MESSAGE_ERROR
	 */
	private $_messageError = self::MESSAGE_ERROR;

	/**
	 * @var string $_actionConfirm = self::ACTION_CONFIRM
	 */
	private $_actionConfirm = self::ACTION_CONFIRM;

	/**
	 * @var string $_actionClose = self::ACTION_CLOSE
	 */
	private $_actionClose = self::ACTION_CLOSE;

	/**
	 * @param string $id
	 * @param string $header
	 * @param string $content
	 * @param string $messageWait = self::MESSAGE_WAIT
	 * @param string $messageSuccess = self::MESSAGE_SUCCESS
	 * @param string $messageError = self::MESSAGE_ERROR
	 * @param string $actionConfirm = self::ACTION_CONFIRM
	 * @param string $actionClose = self::ACTION_CLOSE
	 */
	public function __construct ($id, $header, $content, $messageWait = self::MESSAGE_WAIT, $messageSuccess = self::MESSAGE_SUCCESS, $messageError = self::MESSAGE_ERROR, $actionConfirm = self::ACTION_CONFIRM, $actionClose = self::ACTION_CLOSE) {
		$this->_id = $id;
		$this->_header = $header;
		$this->_content = $content;
		$this->_messageWait = $messageWait;
		$this->_messageSuccess = $messageSuccess;
		$this->_messageError = $messageError;
		$this->_actionConfirm = $actionConfirm;
		$this->_actionClose = $actionClose;
	}

	/**
	 * @param string|null $wait
	 * @param string|null $success
	 * @param string|null $error
	 *
	 * @return QuarkViewDialogFragment
	 */
	public function Message ($wait = null, $success = null, $error = null) {
		if ($wait !== null)
			$this->_messageWait = $wait;

		if ($success !== null)
			$this->_messageSuccess = $success;

		if ($error !== null)
			$this->_messageError = $error;

		return $this;
	}

	/**
	 * @param string|null $confirm
	 * @param string|null $close
	 *
	 * @return QuarkViewDialogFragment
	 */
	public function Action ($confirm = null, $close = null) {
		if ($confirm !== null)
			$this->_actionConfirm = $confirm;

		if ($close !== null)
			$this->_actionClose = $close;

		return $this;
	}

	/**
	 * @return string
	 */
	public function CompileFragment () {
		return '
			<div class="quark-dialog" id="' . $this->_id . '">
				<h3>' . $this->_header . '</h3>
				' . $this->_content . '<br />
				<br />
				<div class="quark-message info fa fa-info-circle quark-dialog-state wait">' . $this->_messageWait . '</div>
				<div class="quark-message ok fa fa-check-circle quark-dialog-state success">' . $this->_messageSuccess . '</div>
				<div class="quark-message warn fa fa-warning quark-dialog-state error">' . $this->_messageError . '</div>
				<br />
				<br />
				<a class="quark-button block white quark-dialog-close">' . $this->_actionClose . '</a>
				<a class="quark-button block ok quark-dialog-confirm">' . $this->_actionConfirm . '</a>
			</div>
		';
	}
}

/**
 * Class QuarkViewDynamicListFragment
 *
 * @package Quark\ViewResources\Quark\JS
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