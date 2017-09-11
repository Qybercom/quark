<?php
namespace Quark\Extensions\OAuth;

use Quark\IQuarkAuthorizableModel;

use Quark\QuarkArchException;

/**
 * Trait OAuthAuthorizableModelBehavior
 *
 * @package Quark\Extensions\OAuth
 */
trait OAuthAuthorizableModelBehavior {
	/**
	 * @var OAuthToken $_token
	 */
	private $_token;

	/**
	 * @var OAuthError $_error
	 */
	private $_error;

	/**
	 * @throws QuarkArchException
	 */
	private function _oauth_check () {
		if (!($this instanceof IQuarkAuthorizableModel))
			throw new QuarkArchException('[OAuthAuthorizableModelBehavior] Model of class ' . get_class($this) . ' is not a IQuarkAuthorizableModel');
	}

	/**
	 * @param OAuthToken $token
	 *
	 * @return $this
	 *
	 * @throws QuarkArchException
	 */
	public function OAuthToken (OAuthToken $token) {
		$this->_oauth_check();

		$this->_token = $token;

		return $this;
	}

	/**
	 * @param OAuthError $error
	 *
	 * @return $this
	 *
	 * @throws QuarkArchException
	 */
	public function OAuthError (OAuthError $error) {
		$this->_oauth_check();

		$this->_error = $error;

		return $this;
	}

	/**
	 * @return OAuthToken
	 */
	public function OAuthModelSuccess () {
		return $this->_token;
	}

	/**
	 * @return OAuthError
	 */
	public function OAuthModelError () {
		return $this->_error;
	}
}