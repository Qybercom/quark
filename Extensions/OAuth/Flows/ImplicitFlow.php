<?php
namespace Quark\Extensions\OAuth\Flows;

use Quark\QuarkURI;
use Quark\QuarkDTO;

use Quark\Extensions\OAuth\IQuarkOAuthFlow;
use Quark\Extensions\OAuth\OAuthConfig;
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
	 * @var bool $_authorize = false
	 */
	private $_authorize = false;

	/**
	 * @var string $_redirect = ''
	 */
	private $_redirect = '';

	/**
	 * @var string $_state = ''
	 */
	private $_state = '';

	/**
	 * @var string $_signature = ''
	 */
	private $_signature = '';

	/**
	 * @param QuarkDTO $request
	 *
	 * @return bool
	 */
	public function OAuthFlowRecognize (QuarkDTO $request) {
		$url = OAuthConfig::URLAllowed($request);
		if (!$url) return false;

		$this->_authorize = $url == OAuthConfig::URL_AUTHORIZE
			&& isset($request->response_type)
			&& $request->response_type == OAuthConfig::RESPONSE_TOKEN;

		$this->_oauthFlowInit($request);

		$this->_redirect = urldecode($request->redirect_uri);
		$this->_state = $request->state;
		$this->_signature = $request->Signature();

		return $this->_authorize;
	}

	/**
	 * @param OAuthToken $token
	 *
	 * @return QuarkDTO|OAuthError
	 */
	public function OAuthFlowSuccess (OAuthToken $token) {
		$redirect = QuarkURI::FromURI($this->_redirect);
		$redirect->AppendFragment($token->ExtractOAuth());

		if ($this->_state)
			$redirect->AppendQuery(array('state' => $this->_state));

		return QuarkDTO::ForRedirect($redirect->URI(true));
	}

	/**
	 * @return bool
	 */
	public function OAuthFlowRequiresAuthentication () {
		return $this->_authorize && $this->_signature != $this->_session->Signature();
	}

	/**
	 * @return string
	 */
	public function OAuthFlowRedirectURI () {
		return $this->_redirect;
	}

	/**
	 * @return string
	 */
	public function OAuthFlowState () {
		return $this->_state;
	}

	/**
	 * @return string
	 */
	public function OAuthFlowSignature () {
		return $this->_signature;
	}
}