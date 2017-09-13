<?php
namespace Quark\Extensions\OAuth\Flows;

use Quark\QuarkDTO;
use Quark\QuarkJSONIOProcessor;

use Quark\Extensions\OAuth\IQuarkOAuthFlow;
use Quark\Extensions\OAuth\OAuthConfig;
use Quark\Extensions\OAuth\OAuthToken;
use Quark\Extensions\OAuth\OAuthError;
use Quark\Extensions\OAuth\OAuthFlowBehavior;

/**
 * Class ClientCredentialsFlow
 *
 * @package Quark\Extensions\OAuth\Flows
 */
class ClientCredentialsFlow implements IQuarkOAuthFlow {
	use OAuthFlowBehavior;

	/**
	 * @param QuarkDTO $request
	 *
	 * @return bool
	 */
	public function OAuthFlowRecognize (QuarkDTO $request) {
		$url = OAuthConfig::URLAllowed($request);
		if (!$url) return false;

		$client = OAuthConfig::URL_TOKEN
			&& isset($request->grant_type)
			&& $request->grant_type == OAuthConfig::GRANT_CLIENT_CREDENTIALS;

		$this->_oauthFlowInit($request);

		return $client;
	}

	/**
	 * @return bool
	 */
	public function OAuthFlowRequiresAuthentication () {
		return false;
	}

	/**
	 * @param OAuthToken $token
	 *
	 * @return QuarkDTO|OAuthError
	 */
	public function OAuthFlowSuccess (OAuthToken $token) {
		$response = QuarkDTO::ForResponse(new QuarkJSONIOProcessor());
		$response->Data($token->ExtractOAuth());

		return $response;
	}
}