<?php
namespace Quark\Extensions\OAuth\Flows;

use Quark\QuarkDTO;

use Quark\Extensions\OAuth\IQuarkOAuthFlow;
use Quark\Extensions\OAuth\OAuthToken;

/**
 * Class AuthorizationCodeFlow
 *
 * @package Quark\Extensions\OAuth\Flows
 */
class AuthorizationCodeFlow implements IQuarkOAuthFlow {
	/**
	 * @param QuarkDTO $request
	 *
	 * @return mixed
	 */
	public function OAuthFlowServerAuthorize (QuarkDTO $request) {
		// TODO: Implement OAuthFlowServerAuthorize() method.
	}

	/**
	 * @param QuarkDTO $request
	 *
	 * @return OAuthToken
	 */
	public function OAuthFlowServerToken (QuarkDTO $request) {
		// TODO: Implement OAuthFlowServerToken() method.
	}
}