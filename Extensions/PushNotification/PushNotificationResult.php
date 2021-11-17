<?php
namespace Quark\Extensions\PushNotification;

/**
 * Class PushNotificationResult
 *
 * @package Quark\Extensions\PushNotification
 */
class PushNotificationResult {
	/**
	 * @var int $_countSuccess = 0
	 */
	private $_countSuccess = 0;

	/**
	 * @var int $_countFailure = 0
	 */
	private $_countFailure = 0;

	/**
	 * @var int $_countCanonical = 0
	 */
	private $_countCanonical = 0;

	/**
	 * @var int $_countRounds = 0
	 */
	private $_countRounds = 0;

	/**
	 * @param int $count = 0
	 *
	 * @return int
	 */
	public function CountSuccess ($count = 0) {
		if (func_num_args() != 0)
			$this->_countSuccess = $count;

		return $this->_countSuccess;
	}

	/**
	 * @param int $count = 0
	 *
	 * @return PushNotificationResult
	 */
	public function CountSuccessAppend ($count = 0) {
		$this->_countSuccess += $count;

		return $this;
	}

	/**
	 * @param int $count = 0
	 *
	 * @return int
	 */
	public function CountFailure ($count = 0) {
		if (func_num_args() != 0)
			$this->_countFailure = $count;

		return $this->_countFailure;
	}

	/**
	 * @param int $count = 0
	 *
	 * @return PushNotificationResult
	 */
	public function CountFailureAppend ($count = 0) {
		$this->_countFailure += $count;

		return $this;
	}

	/**
	 * @param int $count = 0
	 *
	 * @return int
	 */
	public function CounCanonical ($count = 0) {
		if (func_num_args() != 0)
			$this->_countCanonical = $count;

		return $this->_countCanonical;
	}

	/**
	 * @param int $count = 0
	 *
	 * @return PushNotificationResult
	 */
	public function CountCanonicalAppend ($count = 0) {
		$this->_countCanonical += $count;

		return $this;
	}

	/**
	 * @param int $count = 0
	 *
	 * @return int
	 */
	public function CountRounds ($count = 0) {
		if (func_num_args() != 0)
			$this->_countRounds = $count;

		return $this->_countRounds;
	}
}