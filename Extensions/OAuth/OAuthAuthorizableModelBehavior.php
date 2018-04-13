<?php
namespace Quark\Extensions\OAuth;

use Quark\Quark;
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
	 * @return OAuthToken
	 */
	public function OAuthModelToken () {
		return $this->_token;
	}

	/**
	 * @return OAuthError
	 */
	public function OAuthModelError () {
		return $this->_error;
	}

	/**
	 * @param string $canonical = ''
	 * @param string $redirect = ''
	 *
	 * @return bool
	 */
	public function OAuthRedirectAllowed ($canonical = '', $redirect = '') {
		return strpos($redirect, $canonical) !== false;
	}

	/**
	 * @param string $name = ''
	 *
	 * @return OAuthServer
	 */
	public function OAuthServer ($name = '') {
		$server = Quark::Config()->AuthorizationProvider($name)->Provider();

		return $server instanceof OAuthServer ? $server : null;
	}

	/**
	 * @param string $name = ''
	 * @param IQuarkOAuthFlow $flow = null
	 *
	 * @return mixed
	 *
	 * @throws QuarkArchException
	 */
	public function OAuthFlowProcess ($name = '', IQuarkOAuthFlow $flow = null) {
		$this->_oAuthCheck();

		/**
		 * @var IQuarkOAuthAuthorizableModel|OAuthAuthorizableModelBehavior $this
		 */

		return $this->OAuthServer($name)->OAuthFlowProcess($this, $flow);
	}

	/**
	 * @throws QuarkArchException
	 */
	private function _oAuthCheck () {
		if (!($this instanceof IQuarkOAuthAuthorizableModel))
			throw new QuarkArchException('[OAuthAuthorizableModelBehavior] Model of class ' . get_class($this) . ' is not a IQuarkOAuthAuthorizableModel');
	}

	/**
	 * @param OAuthToken $token
	 *
	 * @return $this
	 *
	 * @throws QuarkArchException
	 */
	public function OAuthToken (OAuthToken $token) {
		$this->_oAuthCheck();

		$this->_token = $token;

		return $this;
	}

	/**
	 * @param OAuthError $error
	 *
	 * @return null
	 *
	 * @throws QuarkArchException
	 */
	public function OAuthError (OAuthError $error) {
		$this->_oAuthCheck();

		$this->_error = $error;

		return $this;
	}

	/**
	 * @param string $description = ''
	 * @param string $uri = ''
	 * @param string $state = ''
	 *
	 * @return null
	 */
	public function OAuthErrorInternalError ($description = '', $uri = '', $state = '') {
		return $this->OAuthError(new OAuthError(OAuthError::INTERNAL_ERROR, $description, $uri, $state));
	}

	/**
	 * @param string $description = ''
	 * @param string $uri = ''
	 * @param string $state = ''
	 *
	 * @return null
	 */
	public function OAuthErrorAccessDenied ($description = '', $uri = '', $state = '') {
		return $this->OAuthError(new OAuthError(OAuthError::ACCESS_DENIED, $description, $uri, $state));
	}

	/**
	 * @param string $description = ''
	 * @param string $uri = ''
	 * @param string $state = ''
	 *
	 * @return null
	 */
	public function OAuthErrorUnauthorizedClient ($description = '', $uri = '', $state = '') {
		return $this->OAuthError(new OAuthError(OAuthError::UNAUTHORIZED_CLIENT, $description, $uri, $state));
	}

	/**
	 * @param string $description = ''
	 * @param string $uri = ''
	 * @param string $state = ''
	 *
	 * @return null
	 */
	public function OAuthErrorInvalidRequest ($description = '', $uri = '', $state = '') {
		return $this->OAuthError(new OAuthError(OAuthError::INVALID_REQUEST, $description, $uri, $state));
	}

	/**
	 * @param string $description = ''
	 * @param string $uri = ''
	 * @param string $state = ''
	 *
	 * @return null
	 */
	public function OAuthErrorInvalidScope ($description = '', $uri = '', $state = '') {
		return $this->OAuthError(new OAuthError(OAuthError::INVALID_SCOPE, $description, $uri, $state));
	}

	/**
	 * @param string $description = ''
	 * @param string $uri = ''
	 * @param string $state = ''
	 *
	 * @return null
	 */
	public function OAuthErrorInvalidClient ($description = '', $uri = '', $state = '') {
		return $this->OAuthError(new OAuthError(OAuthError::INVALID_CLIENT, $description, $uri, $state));
	}

	/**
	 * @param string $description = ''
	 * @param string $uri = ''
	 * @param string $state = ''
	 *
	 * @return null
	 */
	public function OAuthErrorInvalidGrant ($description = '', $uri = '', $state = '') {
		return $this->OAuthError(new OAuthError(OAuthError::INVALID_GRANT, $description, $uri, $state));
	}

	/**
	 * @param string $description = ''
	 * @param string $uri = ''
	 * @param string $state = ''
	 *
	 * @return null
	 */
	public function OAuthErrorInvalidToken ($description = '', $uri = '', $state = '') {
		return $this->OAuthError(new OAuthError(OAuthError::INVALID_TOKEN, $description, $uri, $state));
	}

	/**
	 * @param string $description = ''
	 * @param string $uri = ''
	 * @param string $state = ''
	 *
	 * @return null
	 */
	public function OAuthErrorUnsupportedGrantType ($description = '', $uri = '', $state = '') {
		return $this->OAuthError(new OAuthError(OAuthError::UNSUPPORTED_GRANT_TYPE, $description, $uri, $state));
	}

	/**
	 * @param string $description = ''
	 * @param string $uri = ''
	 * @param string $state = ''
	 *
	 * @return null
	 */
	public function OAuthErrorUnsupportedResponseType ($description = '', $uri = '', $state = '') {
		return $this->OAuthError(new OAuthError(OAuthError::UNSUPPORTED_RESPONSE_TYPE, $description, $uri, $state));
	}

	/**
	 * @param string $description = ''
	 * @param string $uri = ''
	 * @param string $state = ''
	 *
	 * @return null
	 */
	public function OAuthErrorSlowDown ($description = '', $uri = '', $state = '') {
		return $this->OAuthError(new OAuthError(OAuthError::SLOW_DOWN, $description, $uri, $state));
	}

	/**
	 * @param string $description = ''
	 * @param string $uri = ''
	 * @param string $state = ''
	 *
	 * @return null
	 */
	public function OAuthErrorAuthorizationPending ($description = '', $uri = '', $state = '') {
		return $this->OAuthError(new OAuthError(OAuthError::AUTHORIZATION_PENDING, $description, $uri, $state));
	}
}