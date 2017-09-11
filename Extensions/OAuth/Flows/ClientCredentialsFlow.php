<?php
namespace Quark\Extensions\OAuth\Flows;

use Quark\QuarkDTO;
use Quark\QuarkKeyValuePair;

use Quark\Extensions\OAuth\IQuarkOAuthFlow;
use Quark\Extensions\OAuth\OAuthToken;
use Quark\Extensions\OAuth\OAuthError;

/**
 * Class ClientCredentialsFlow
 *
 * @package Quark\Extensions\OAuth\Flows
 */
class ClientCredentialsFlow implements IQuarkOAuthFlow {
	/**
	 * @param QuarkDTO $request
	 *
	 * @return bool
	 */
	public function OAuthFlowRecognize (QuarkDTO $request) {
		// TODO: Implement OAuthFlowRecognize() method.
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
	 * @return QuarkKeyValuePair
	 */
	public function OAuthFlowClient () {
		// TODO: Implement OAuthFlowClient() method.
	}
}