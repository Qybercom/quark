<?php
namespace Quark\Extensions\OAuth\Flows;

use Quark\QuarkDTO;

use Quark\Extensions\OAuth\IQuarkOAuthFlow;
use Quark\Extensions\OAuth\OAuthToken;
use Quark\Extensions\OAuth\OAuthError;
use Quark\Extensions\OAuth\OAuthFlowBehavior;

/**
 * Class ImplicitFlow
 *
 * @package Quark\Extensions\OAuth\Flows
 */
class ImplicitFlow implements IQuarkOAuthFlow {
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
		return true;
	}
}