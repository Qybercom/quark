<?php
namespace Quark\Extensions\OAuth;

use Quark\QuarkDTO;
use Quark\QuarkModel;

/**
 * Interface IQuarkOAuthProvider
 *
 * @package Quark\Extensions\OAuth
 */
interface IQuarkOAuthProvider {
	/**
	 * @param OAuthToken $token
	 *
	 * @return IQuarkOAuthConsumer
	 */
	public function OAuthConsumer(OAuthToken $token);

	/**
	 * @param string $appId
	 * @param string $appSecret
	 *
	 * @return mixed
	 */
	public function OAuthApplication($appId, $appSecret);

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

	/**
	 * @param QuarkDTO $request
	 * @param string $redirect
	 *
	 * @return QuarkModel|OAuthToken
	 */
	public function OAuthTokenFromRequest(QuarkDTO $request, $redirect);

	/**
	 * @param string $url
	 * @param QuarkDTO $request
	 * @param QuarkDTO $response
	 *
	 * @return QuarkDTO|null
	 *
	 * @throws OAuthAPIException
	 */
	public function OAuthAPI($url, QuarkDTO $request, QuarkDTO $response);
}