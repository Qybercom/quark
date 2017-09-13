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
	 * @return bool
	 */
	public function OAuthFlowRequiresAuthentication();

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
	public function OAuthFlowSession(QuarkSession $session = null);

	/**
	 * @return string[]
	 */
	public function OAuthFlowScope();
}