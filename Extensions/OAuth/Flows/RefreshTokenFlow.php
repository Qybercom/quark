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
 * Class RefreshTokenFlow
 *
 * @package Quark\Extensions\OAuth\Flows
 */
class RefreshTokenFlow implements IQuarkOAuthFlow {
	use OAuthFlowBehavior;

	/**
	 * @var string $_refresh = ''
	 */
	private $_refresh = '';

	/**
	 * @param QuarkDTO $request
	 *
	 * @return bool
	 */
	public function OAuthFlowRecognize (QuarkDTO $request) {
		$url = OAuthConfig::URLAllowed($request);
		if (!$url) return false;

		$refresh = OAuthConfig::URL_TOKEN
			&& isset($request->grant_type)
			&& $request->grant_type == OAuthConfig::GRANT_REFRESH_TOKEN;

		$this->_oauthFlowInit($request);

		$this->_refresh = $request->refresh_token;

		return $refresh;
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

	/**
	 * @return string
	 */
	public function OAuthFlowRefreshToken () {
		return $this->_refresh;
	}
}