<?php
namespace Quark\Extensions\OAuth\Flows;

use Quark\QuarkDTO;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkKeyValuePair;

use Quark\Extensions\OAuth\IQuarkOAuthFlow;
use Quark\Extensions\OAuth\OAuthConfig;
use Quark\Extensions\OAuth\OAuthToken;
use Quark\Extensions\OAuth\OAuthError;
use Quark\Extensions\OAuth\OAuthFlowBehavior;

/**
 * Class PasswordCredentialsFlow
 *
 * @package Quark\Extensions\OAuth\Flows
 */
class PasswordCredentialsFlow implements IQuarkOAuthFlow {
	use OAuthFlowBehavior;

	/**
	 * @var QuarkKeyValuePair $_user
	 */
	private $_user;

	/**
	 * @param QuarkDTO $request
	 *
	 * @return bool
	 */
	public function OAuthFlowRecognize (QuarkDTO $request) {
		$url = OAuthConfig::URLAllowed($request);
		if (!$url) return false;

		$password = OAuthConfig::URL_TOKEN
			&& isset($request->grant_type)
			&& $request->grant_type == OAuthConfig::GRANT_PASSWORD;

		$this->_oAuthFlowInit($request);

		$this->_user = new QuarkKeyValuePair($request->username, $request->password);

		return $password;
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
	 * @return QuarkKeyValuePair
	 */
	public function OAuthFlowUser () {
		return $this->_user;
	}

	/**
	 * @return string
	 */
	public function OAuthFlowModelProcessMethod () {
		return 'OAuthFlowPasswordCredentials';
	}
}