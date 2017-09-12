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
use Quark\QuarkURI;

/**
 * Class AuthorizationCodeFlow
 *
 * @package Quark\Extensions\OAuth\Flows
 */
class AuthorizationCodeFlow implements IQuarkOAuthFlow {
	use OAuthFlowBehavior;

	/**
	 * @var bool $_authorize = false
	 */
	private $_stageAuthorize = false;

	/**
	 * @var bool $_stageToken = false
	 */
	private $_stageToken = false;

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
	 * @var string $_state = ''
	 */
	private $_state = '';

	/**
	 * @var string $_signature = ''
	 */
	private $_signature = '';

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
		$this->_state = $request->state;
		$this->_signature = $request->Signature();

		return $this->_stageAuthorize || $this->_stageToken;
	}

	/**
	 * @param OAuthToken $token
	 *
	 * @return QuarkDTO|OAuthError
	 */
	public function OAuthFlowSuccess (OAuthToken $token) {
		if ($this->_stageAuthorize) {
			$query = array(
				'code' => $token->code
			);

			if ($this->_state)
				$query['state'] = $this->_state;

			$redirect = QuarkURI::FromURI($this->_redirect);
			$redirect->AppendQuery($query);

			$response = QuarkDTO::ForRedirect($redirect->URI(true));
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
	 * @return bool
	 */
	public function OAuthFlowRequiresAuthentication () {
		return $this->_stageAuthorize && $this->_signature != $this->_session->Signature();
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