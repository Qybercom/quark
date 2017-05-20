<?php
namespace Quark\Extensions\OAuth;

/**
 * Class OAuthClient
 *
 * @package Quark\Extensions\OAuth
 */
class OAuthClient implements IQuarkOAuthConsumer {
	/**
	 * @var OAuthToken $_token
	 */
	private $_token;

	/**
	 * @var IQuarkOAuthProvider $_provider
	 */
	private $_provider;

	/**
	 * @param OAuthToken $token
	 *
	 * @return mixed
	 */
	public function OAuthToken (OAuthToken $token) {
		$this->_token = $token;
	}

	/**
	 * @param IQuarkOAuthProvider $provider
	 *
	 * @return mixed
	 */
	public function OAuthProvider (IQuarkOAuthProvider $provider) {
		$this->_provider = $provider;
	}
}