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
	 * @var string $_redirect = ''
	 */
	private $_redirect = '';

	/**
	 * @throws QuarkArchException
	 */
	private function _oauth_check () {
		if (!($this instanceof IQuarkAuthorizableModel))
			throw new QuarkArchException('[OAuthAuthorizableModelBehavior] Model of class ' . get_class($this) . ' is not a IQuarkAuthorizableModel');
	}

	/**
	 * @param OAuthToken $token
	 * @param string $redirect = ''
	 *
	 * @return $this
	 *
	 * @throws QuarkArchException
	 */
	public function OAuthToken (OAuthToken $token, $redirect = '') {
		$this->_oauth_check();

		$this->_token = $token;
		$this->_redirect = $redirect;

		return $this;
	}

	/**
	 * @param OAuthError $error
	 * @param string $redirect = ''
	 *
	 * @return $this
	 *
	 * @throws QuarkArchException
	 */
	public function OAuthError (OAuthError $error, $redirect = '') {
		$this->_oauth_check();

		$this->_error = $error;
		$this->_redirect = $redirect;

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

	/**
	 * @return string
	 */
	public function OAuthModelRedirect () {
		return $this->_redirect;
	}

	/**
	 * @param string $canonical = ''
	 * @param string $redirect = ''
	 *
	 * @return bool
	 */
	public function OAuthModelRedirectAllowed ($canonical = '', $redirect = '') {
		return strpos($redirect, $canonical) !== false;
	}
}