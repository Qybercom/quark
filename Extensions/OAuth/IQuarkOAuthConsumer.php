<?php
namespace Quark\Extensions\OAuth;

/**
 * Interface IQuarkOAuthConsumer
 *
 * @package Quark\Extensions\OAuth
 */
interface IQuarkOAuthConsumer {
	/**
	 * @param string $config
	 *
	 * @return mixed
	 */
	public function OAuthConfig($config);

	/**
	 * @param IQuarkOAuthProvider $provider
	 *
	 * @return mixed
	 */
	public function OAuthProvider(IQuarkOAuthProvider $provider);

	/**
	 * @param OAuthToken $token
	 *
	 * @return mixed
	 */
	public function OAuthToken(OAuthToken $token);

	/**
	 * @param string $redirect
	 * @param string[] $scope
	 *
	 * @return string
	 */
	public function OAuthLoginURL($redirect, $scope);

	/**
	 * @param string $redirect
	 *
	 * @return string
	 */
	public function OAuthLogoutURL($redirect);
}