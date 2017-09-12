<?php
namespace Quark\Extensions\OAuth;

use Quark\QuarkDTO;
use Quark\QuarkKeyValuePair;
use Quark\QuarkSession;

/**
 * Interface IQuarkOAuthFlow
 *
 * @package Quark\Extensions\OAuth
 */
interface IQuarkOAuthFlow {
	/**
	 * @param QuarkDTO $request
	 *
	 * @return bool
	 */
	public function OAuthFlowRecognize(QuarkDTO $request);

	/**
	 * @return string[]
	 */
	public function OAuthFlowScope();

	/**
	 * @param OAuthToken $token
	 *
	 * @return QuarkDTO|OAuthError
	 */
	public function OAuthFlowSuccess(OAuthToken $token);

	/**
	 * @return QuarkKeyValuePair
	 */
	public function OAuthFlowClient();

	/**
	 * @param QuarkSession $session = null
	 *
	 * @return QuarkSession
	 */
	public function OAuthFlowUser(QuarkSession $session = null);

	/**
	 * @return bool
	 */
	public function OAuthFlowRequiresAuthentication();
}