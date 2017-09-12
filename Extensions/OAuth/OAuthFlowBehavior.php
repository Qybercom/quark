<?php
namespace Quark\Extensions\OAuth;

use Quark\QuarkKeyValuePair;
use Quark\QuarkSession;

/**
 * Class OAuthFlowBehavior
 *
 * @package Quark\Extensions\OAuth
 */
trait OAuthFlowBehavior {
	/**
	 * @var QuarkKeyValuePair $_client
	 */
	private $_client;

	/**
	 * @var QuarkSession $_session
	 */
	private $_session;

	/**
	 * @return QuarkKeyValuePair
	 */
	public function OAuthFlowClient () {
		return $this->_client;
	}

	/**
	 * @param QuarkSession $session = null
	 *
	 * @return QuarkSession
	 */
	public function OAuthFlowUser (QuarkSession $session = null) {
		if (func_num_args() != 0)
			$this->_session = $session;

		return $this->_session;
	}
}