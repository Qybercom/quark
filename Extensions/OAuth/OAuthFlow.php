<?php
namespace Quark\Extensions\OAuth;

use Quark\QuarkKeyValuePair;

/**
 * Class OAuthFlow
 *
 * @package Quark\Extensions\OAuth
 */
class OAuthFlow {
	/**
	 * @var IQuarkOAuthFlow $_flow
	 */
	private $_flow;

	/**
	 * @var QuarkKeyValuePair $_authorization
	 */
	private $_authorization;

	/**
	 * @param IQuarkOAuthFlow $flow = null
	 * @param QuarkKeyValuePair $authorization = null
	 */
	public function __construct (IQuarkOAuthFlow $flow = null, QuarkKeyValuePair $authorization = null) {
		$this->Flow($flow);
		$this->Authorization($authorization);
	}

	/**
	 * @param IQuarkOAuthFlow $flow = null
	 *
	 * @return IQuarkOAuthFlow
	 */
	public function &Flow (IQuarkOAuthFlow $flow = null) {
		if (func_num_args() != 0)
			$this->_flow = $flow;

		return $this->_flow;
	}

	/**
	 * @param QuarkKeyValuePair $authorization = null
	 *
	 * @return QuarkKeyValuePair
	 */
	public function &Authorization (QuarkKeyValuePair $authorization = null) {
		if (func_num_args() != 0)
			$this->_authorization = $authorization;

		return $this->_authorization;
	}

	/**
	 * @return bool
	 */
	public function AuthorizationProvided () {
		return $this->_authorization instanceof QuarkKeyValuePair;
	}

	/**
	 * @return bool
	 */
	public function RequiresAuthentication () {
		return $this->_flow == null ? false : $this->_flow->OAuthFlowRequiresAuthentication();
	}

	/**
	 * @return QuarkKeyValuePair
	 */
	public function Client () {
		return $this->_flow == null ? null : $this->_flow->OAuthFlowClient();
	}

	/**
	 * @return mixed
	 */
	public function AccessToken () {
		return $this->_authorization == null ? null : $this->_authorization->Value();
	}
}