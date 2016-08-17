<?php
namespace Quark\ViewResources\Quark\QuarkControls\ViewFragments;

use Quark\IQuarkViewFragment;

/**
 * Class QuarkViewDialogFragment
 *
 * @package Quark\ViewResources\Quark\QuarkControls\ViewFragments
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
			<form class="quark-dialog" id="' . $this->_id . '">
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
			</form>
		';
	}
}