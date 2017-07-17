<?php
namespace Quark\Extensions\OAuth;

use Quark\Quark;
use Quark\QuarkDate;
use Quark\QuarkDTO;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkKeyValuePair;
use Quark\QuarkModel;

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

		$_sign = array();

		foreach ($params as $key => $value) {
			$header[] = rawurlencode($key) . '="' . rawurlencode($value) .'"';
			$_sign[] = rawurlencode($key) . '=' . rawurlencode($value);
		}

		$key = rawurlencode($this->_appSecret) . '&' . rawurlencode(isset($this->_token->oauth_token_secret) ? $this->_token->oauth_token_secret : '');
		$data = rawurlencode($method) . '&' . rawurlencode($url) . '&' . rawurlencode(implode('&', $_sign));

		$sign = base64_encode(hash_hmac('sha1', $data, $key, true));
		$header[] = 'oauth_signature="' . rawurlencode($sign) . '"';

		return new QuarkKeyValuePair('OAuth', implode(',', $header));
	}

	/**
	 * @var string $_verifier = ''
	 */
	private $_verifier = '';

	/**
	 * @param QuarkDTO|null $request = null
	 * @param string $urlAccessToken = ''
	 * @param string $base = null
	 * @param string $oauthTokenSecret = null
	 *
	 * @return OAuthToken
	 */
	public function OAuth1_0a_TokenFromRequest (QuarkDTO $request = null, $urlAccessToken = '', $base = null, $oauthTokenSecret = null) {
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

		$req = QuarkDTO::ForPOST(new QuarkFormIOProcessor());
		$req->Data(array('oauth_verifier' => $request->oauth_verifier));

		$token = $this->OAuthAPI($urlAccessToken, $req, new QuarkDTO(new QuarkFormIOProcessor()), $base);

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