<?php
namespace Quark\Extensions\OAuth;

/**
 * Interface IQuarkOAuthConsumer
 *
 * @package Quark\Extensions\OAuth
 */
interface IQuarkOAuthConsumer {
	/**
	 * @param OAuthToken $token
	 *
	 * @return mixed
	 */
	public function OAuthToken(OAuthToken $token);

	/**
	 * @param IQuarkOAuthProvider $provider
	 *
	 * @return mixed
	 */
	public function OAuthProvider(IQuarkOAuthProvider $provider);
}