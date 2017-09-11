<?php
namespace Quark\Extensions\OAuth;

use Quark\QuarkDTO;
use Quark\QuarkKeyValuePair;

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
	 * @param OAuthToken $token
	 *
	 * @return QuarkDTO|OAuthError
	 */
	public function OAuthFlowSuccess(OAuthToken $token);

	/**
	 * @return QuarkKeyValuePair
	 */
	public function OAuthFlowClient();
}