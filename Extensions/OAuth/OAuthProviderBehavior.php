<?php
namespace Quark\Extensions\OAuth;

use Quark\Quark;
use Quark\QuarkDate;
use Quark\QuarkDTO;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkKeyValuePair;
use Quark\QuarkModel;
use Quark\QuarkObject;

/**
 * Trait OAuthProviderBehavior
 *
 * @package Quark\Extensions\OAuth
 */
trait OAuthProviderBehavior {
	/**
	 * @var string $_appId
	 */
	private $_appId = '';

	/**
	 * @var string $_appSecret = ''
	 */
	private $_appSecret = '';

	/**
	 * @var OAuthToken $_token = ''
	 */
	private $_token = '';

	/**
	 * @var string $_callback = ''
	 */
	private $_callback = '';

	/**
	 * @var string $_verifier = ''
	 */
	private $_verifier = '';

	/**
	 * @var array $_params = []
	 */
	private $_params = array();

	/**
	 * @param OAuthAPIException $e
	 * @param string $action = ''
	 * @param string $message = ''
	 * @param $out = null
	 *
	 * @return mixed
	 */
	private function _oauth_error (OAuthAPIException $e, $action = '', $message = '', $out = null) {
		Quark::Log('[OAuth.' . QuarkObject::ClassOf($this) . '::' . $action . '] ' . $message . '. API error:', Quark::LOG_WARN);

		Quark::Trace($e->Request());
		Quark::Trace($e->Response());

		return $out;
	}

	/**
	 * @return array
	 */
	public function OAuth1_0a_Params () {
		return array(
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_version' => '1.0',
			'oauth_timestamp' => QuarkDate::Now()->Timestamp(),
			'oauth_nonce' => Quark::GuID(),
			'oauth_consumer_key' => $this->_appId
		);
	}

	/**
	 * @param string $method = ''
	 * @param string $url = ''
	 * @param array $params = []
	 *
	 * @return string
	 */
	public function OAuth1_0a_Signature ($method = '', $url = '', $params = []) {
		$sign = array();

		uksort($params, 'strcmp');

		foreach ($params as $key => &$value)
			$sign[] = $this->_param_encode($key) . '=' . $this->_param_encode($value);

		$key = $this->_param_encode($this->_appSecret) . '&' . $this->_param_encode(isset($this->_token->oauth_token_secret) ? $this->_token->oauth_token_secret : '');
		$data = $this->_param_encode($method) . '&' . $this->_param_encode($url) . '&' . $this->_param_encode(implode('&', $sign));

		return base64_encode(hash_hmac('sha1', $data, $key, true));
	}

	/**
	 * @param string $input = ''
	 *
	 * @return string
	 */
	private function _param_encode ($input = '') {
		return str_replace('+', ' ', str_replace('%7E', '~', rawurlencode($input)));
	}

	/**
	 * @note if provider API server responds with error - type a placeholder into Callback URL field in the application settings
	 *
	 * @param string $method = ''
	 * @param string $url = ''
	 * @param array $params = []
	 *
	 * @return QuarkKeyValuePair
	 */
	public function OAuth1_0a_AuthorizationHeader ($method = '', $url = '', $params = []) {
		$header = array();
		$now = QuarkDate::Now();

		$params['oauth_signature_method'] = 'HMAC-SHA1';
		$params['oauth_version'] = '1.0';
		$params['oauth_timestamp'] = $now->Timestamp();
		$params['oauth_nonce'] = Quark::GuID();
		$params['oauth_consumer_key'] = $this->_appId;

		if ($this->_callback)
			$params['oauth_callback'] = $this->_callback;

		if ($this->_verifier)
			$params['oauth_verifier'] = $this->_verifier;

		if (isset($this->_token->access_token))
			$params['oauth_token'] = $this->_token->access_token;

		// Parameters are sorted by name, using lexicographical byte value ordering.
		// Ref: Spec: 9.1.1 (1)
		uksort($params, 'strcmp');

		$this->_params = $params;

		$_sign = array();

		foreach ($params as $key => $value) {
			$header[] = $this->_param_encode($key) . '="' . $this->_param_encode($value) . '"';
			$_sign[] = $this->_param_encode($key) . '=' . $this->_param_encode($value);
		}

		$key = $this->_param_encode($this->_appSecret) . '&' . $this->_param_encode(isset($this->_token->oauth_token_secret) ? $this->_token->oauth_token_secret : '');
		$data = $this->_param_encode($method) . '&' . $this->_param_encode($url) . '&' . $this->_param_encode(implode('&', $_sign));

		$sign = base64_encode(hash_hmac('sha1', $data, $key, true));
		$header[] = 'oauth_signature="' . $this->_param_encode($sign) . '"';

		return new QuarkKeyValuePair('OAuth', implode(', ', $header));
	}

	/**
	 * @note This method is useful for those providers, who requires to pass /access_token in URL
	 *
	 * @param string $method = ''
	 * @param string $url = ''
	 * @param array $params = []
	 *
	 * @return QuarkKeyValuePair
	 */
	public function OAuth1_0a_AuthorizationQuery ($method = '', $url = '', $params = []) {
		$params = array_replace($this->OAuth1_0a_Params(), $params);

		if ($this->_callback)
			$params['oauth_callback'] = $this->_callback;

		if ($this->_verifier)
			$params['oauth_verifier'] = $this->_verifier;

		if (isset($this->_token->access_token))
			$params['oauth_token'] = $this->_token->access_token;

		uksort($params, 'strcmp');

		$params['oauth_signature'] = $this->OAuth1_0a_Signature($method, $url, $params);

		uksort($params, 'strcmp');

		return $params;
	}

	/**
	 * @param string $redirect = ''
	 * @param string $url = ''
	 * @param string $base = null
	 *
	 * @return QuarkDTO
	 */
	public function OAuth1_0a_RequestToken ($redirect = '', $url = '', $base = null) {
		/**
		 * @var IQuarkOAuthProvider|OAuthProviderBehavior $this
		 */
		if (!($this instanceof IQuarkOAuthProvider)) return null;

		$this->_callback = $redirect;

		return $this->OAuthAPI($url, QuarkDTO::ForPOST(new QuarkFormIOProcessor()), new QuarkDTO(new QuarkFormIOProcessor()), $base);
	}

	/**
	 * @param QuarkDTO|null $request = null
	 * @param string $urlAccessToken = ''
	 * @param string $base = null
	 * @param string $oauthTokenSecret = null
	 * @param QuarkDTO $req = null
	 * @param QuarkDTO $res = null
	 *
	 * @return OAuthToken
	 */
	public function OAuth1_0a_TokenFromRequest (QuarkDTO $request = null, $urlAccessToken = '', $base = null, $oauthTokenSecret = null, $req = null, $res = null) {
		/**
		 * @var IQuarkOAuthProvider|OAuthProviderBehavior $this
		 */
		if (!($this instanceof IQuarkOAuthProvider)) return null;
		if (!isset($request->oauth_token)) return null;
		if (!isset($request->oauth_verifier)) return null;

		$this->_token = new OAuthToken();
		$this->_token->access_token = $request->oauth_token;

		if ($oauthTokenSecret !== null)
			$this->_token->oauth_token_secret = $oauthTokenSecret;

		if ($req == null) {
			$req = QuarkDTO::ForPOST(new QuarkFormIOProcessor());
			$req->Data(array('oauth_verifier' => $request->oauth_verifier));
		}
		if ($res == null) $res = new QuarkDTO(new QuarkFormIOProcessor());

		$token = $this->OAuthAPI($urlAccessToken, $req, $res, $base);

		if (!isset($token->oauth_token)) return null;
		if (!isset($token->oauth_token_secret)) return null;

		$out = new QuarkModel(new OAuthToken(), array(
			'access_token' => $token->oauth_token,
			'oauth_token_secret' => $token->oauth_token_secret
		));

		$this->_token = $out->Model();

		return $out;
	}
}