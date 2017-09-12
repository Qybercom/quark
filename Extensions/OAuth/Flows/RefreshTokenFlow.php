<?php
namespace Quark\Extensions\OAuth\Flows;

use Quark\QuarkDTO;

use Quark\Extensions\OAuth\IQuarkOAuthFlow;
use Quark\Extensions\OAuth\OAuthToken;
use Quark\Extensions\OAuth\OAuthError;
use Quark\Extensions\OAuth\OAuthFlowBehavior;

/**
 * Class RefreshTokenFlow
 *
 * @package Quark\Extensions\OAuth\Flows
 */
class RefreshTokenFlow implements IQuarkOAuthFlow {
	use OAuthFlowBehavior;

	/**
	 * @param QuarkDTO $request
	 *
	 * @return bool
	 */
	public function OAuthFlowRecognize (QuarkDTO $request) {
		// TODO: Implement OAuthFlowRecognize() method.
	}

	/**
	 * @return string[]
	 */
	public function OAuthFlowScope () {
		// TODO: Implement OAuthFlowScope() method.
	}

	/**
	 * @param OAuthToken $token
	 *
	 * @return QuarkDTO|OAuthError
	 */
	public function OAuthFlowSuccess (OAuthToken $token) {
		// TODO: Implement OAuthFlowSuccess() method.
	}

	/**
	 * @return bool
	 */
	public function OAuthFlowRequiresAuthentication () {
		return false;
	}
}