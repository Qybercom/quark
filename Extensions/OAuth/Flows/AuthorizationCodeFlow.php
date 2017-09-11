<?php
namespace Quark\Extensions\OAuth\Flows;

use Quark\QuarkDTO;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkKeyValuePair;

use Quark\Extensions\OAuth\IQuarkOAuthFlow;
use Quark\Extensions\OAuth\OAuthConfig;
use Quark\Extensions\OAuth\OAuthToken;
use Quark\Extensions\OAuth\OAuthError;

/**
 * Class AuthorizationCodeFlow
 *
 * @package Quark\Extensions\OAuth\Flows
 */
class AuthorizationCodeFlow implements IQuarkOAuthFlow {
	/**
	 * @var bool $_authorize = false
	 */
	private $_stageAuthorize = false;

	/**
	 * @var bool $_stageToken = false
	 */
	private $_stageToken = false;

	/**
	 * @var QuarkKeyValuePair $_client
	 */
	private $_client;

	/**
	 * @var string $_redirect = ''
	 */
	private $_redirect = '';

	/**
	 * @var string[] $_scope = []
	 */
	private $_scope = array();

	/**
	 * @var string $_code
	 */
	private $_code = '';

	/**
	 * @return bool
	 */
	public function OAuthFlowStageAuthorize () {
		return $this->_stageAuthorize;
	}

	/**
	 * @return bool
	 */
	public function OAuthFlowStageToken () {
		return $this->_stageToken;
	}

	/**
	 * @param QuarkDTO $request
	 *
	 * @return bool
	 */
	public function OAuthFlowRecognize (QuarkDTO $request) {
		$url = OAuthConfig::URLAllowed($request);

		if (!$url) return false;

		$this->_stageAuthorize = $url == OAuthConfig::URL_AUTHORIZE
			&& isset($request->response_type)
			&& $request->response_type == OAuthConfig::RESPONSE_CODE;

		$this->_stageToken = $url == OAuthConfig::URL_TOKEN
			&& isset($request->grant_type)
			&& $request->grant_type == OAuthConfig::GRANT_AUTHORIZATION_CODE;

		$this->_client = new QuarkKeyValuePair($request->client_id, $request->client_secret);
		$this->_redirect = urldecode($request->redirect_uri);
		$this->_scope = explode(',', $request->scope);
		$this->_code = $request->code;

		return $this->_stageAuthorize || $this->_stageToken;
	}

	/**
	 * @param OAuthToken $token
	 *
	 * @return QuarkDTO|OAuthError
	 */
	public function OAuthFlowSuccess (OAuthToken $token) {
		if ($this->_stageAuthorize) {
			$response = QuarkDTO::ForRedirect($this->_redirect);
			$response->Data($token->ExtractOAuth());

			return $response;
		}

		if ($this->_stageToken) {
			$response = QuarkDTO::ForResponse(new QuarkJSONIOProcessor());
			$response->Data($token->ExtractOAuth());

			return $response;
		}

		return null;
	}

	/**
	 * @return QuarkKeyValuePair
	 */
	public function OAuthFlowClient () {
		return $this->_client;
	}

	/**
	 * @return string
	 */
	public function OAuthFlowRedirectURI () {
		return $this->_redirect;
	}

	/**
	 * @return string[]
	 */
	public function OAuthFlowScope () {
		return $this->_scope;
	}

	/**
	 * @return string
	 */
	public function OAuthFlowCode () {
		return $this->_code;
	}
}