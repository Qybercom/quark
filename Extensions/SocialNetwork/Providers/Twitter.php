<?php
namespace Quark\Extensions\SocialNetwork\Providers;

use Quark\Quark;
use Quark\QuarkDate;
use Quark\QuarkDTO;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkKeyValuePair;
use Quark\QuarkModel;

use Quark\Extensions\OAuth\IQuarkOAuthConsumer;
use Quark\Extensions\OAuth\IQuarkOAuthProvider;
use Quark\Extensions\OAuth\OAuthToken;

use Quark\Extensions\SocialNetwork\IQuarkSocialNetworkProvider;
use Quark\Extensions\SocialNetwork\SocialNetwork;
use Quark\Extensions\SocialNetwork\SocialNetworkUser;
use Quark\Extensions\SocialNetwork\SocialNetworkAPIException;

/**
 * Class Twitter
 *
 * https://github.com/abraham/twitteroauth
 *
 * https://habrahabr.ru/post/145988/
 * https://habrahabr.ru/post/86846/
 *
 * https://oauth.net/core/1.0/#signing_process
 *
 * https://dev.twitter.com/web/sign-in/implementing
 * https://dev.twitter.com/oauth/overview/authorizing-requests
 * https://dev.twitter.com/rest/reference/get/account/verify_credentials
 * https://dev.twitter.com/rest/reference/get/users/lookup
 *
 * @package Quark\Extensions\SocialNetwork\Providers
 */
class Twitter implements IQuarkOAuthProvider, IQuarkSocialNetworkProvider {
	const URL_BASE = 'https://twitter.com/';
	const URL_API = 'https://api.twitter.com';

	/**
	 * @var string $_appId
	 */
	private $_appId = '';

	/**
	 * @var string $_appSecret = ''
	 */
	private $_appSecret = '';

	/**
	 * @var OAuthToken|string $_token = ''
	 */
	private $_token = '';

	/**
	 * @var string $_callback = ''
	 */
	private $_callback = '';

	/**
	 * @param OAuthToken $token
	 *
	 * @return IQuarkOAuthConsumer
	 */
	public function OAuthConsumer (OAuthToken $token) {
		return new SocialNetwork();
	}

	/**
	 * @param string $appId
	 * @param string $appSecret
	 *
	 * @return mixed
	 */
	public function OAuthApplication ($appId, $appSecret) {
		$this->_appId = $appId;
		$this->_appSecret = $appSecret;
	}

	/**
	 * @param string $redirect
	 * @param string[] $scope
	 *
	 * @return string
	 */
	public function OAuthLoginURL ($redirect, $scope) {
		$this->_callback = $redirect;

		$login = $this->SocialNetworkAPI(
			'/oauth/request_token',
			QuarkDTO::ForPOST(new QuarkFormIOProcessor()),
			new QuarkDTO(new QuarkFormIOProcessor())
		);

		return self::URL_API . '/oauth/authenticate?oauth_token=' . $login->oauth_token;
	}

	/**
	 * @param string $redirect
	 *
	 * @return string
	 */
	public function OAuthLogoutURL ($redirect) {
		// TODO: Implement OAuthLogoutURL() method.
	}

	/**
	 * @param QuarkDTO $request
	 * @param string $redirect
	 *
	 * @return QuarkModel|OAuthToken
	 */
	public function OAuthTokenFromRequest (QuarkDTO $request, $redirect) {
		$this->_token = new OAuthToken();
		$this->_token->access_token = $request->oauth_token;

		$req = QuarkDTO::ForPOST(new QuarkFormIOProcessor());
		$req->Data(array('oauth_verifier' => $request->oauth_verifier));

		$token = $this->SocialNetworkAPI('/oauth/access_token', $req, new QuarkDTO(new QuarkFormIOProcessor()));

		$out = new QuarkModel(new OAuthToken(), array(
			'access_token' => $token->oauth_token,
			'oauth_token_secret' => $token->oauth_token_secret
		));

		$this->_token = $out->Model();

		return $out;
	}

	/**
	 * @param string $url = ''
	 * @param QuarkDTO $request = null
	 * @param QuarkDTO $response = null
	 *
	 * @return QuarkDTO|null
	 *
	 * @throws SocialNetworkAPIException
	 */
	public function SocialNetworkAPI ($url = '', QuarkDTO $request = null, QuarkDTO $response = null) {
		if ($request == null) $request = QuarkDTO::ForGET(new QuarkFormIOProcessor());
		if ($response == null) $response = new QuarkDTO(new QuarkJSONIOProcessor());

		$request->Authorization($this->_authorization($request->Method(), self::URL_API . $url));

		$api = QuarkHTTPClient::To(self::URL_API . $url, $request, $response);

		if (isset($api->error))
			throw new SocialNetworkAPIException($request, $response);

		return $api;
	}

	/**
	 * @param string $user
	 *
	 * @return SocialNetworkUser
	 */
	public function SocialNetworkUser ($user) {
		$response = $this->SocialNetworkAPI(
			$user == ''
				? '/1.1/account/verify_credentials.json'
				: '/1.1/users/lookup.json?user_id=' . $user,
			QuarkDTO::ForGET(new QuarkFormIOProcessor()),
			new QuarkDTO(new QuarkJSONIOProcessor())
		);

		if (is_array($response->Data())) $response = $response->Data();
		else $response = array($response);

		if (sizeof($response) == 0 || $response[0] == null) return null;
		$response = $response[0];

		$profile = new SocialNetworkUser($response->id, $response->name);

		$profile->PhotoFromLink($response->profile_image_url_https);
		$profile->Page(self::URL_BASE . $response->screen_name);

		return $profile;
	}

	/**
	 * @param string $user
	 * @param int $count
	 * @param int $offset
	 *
	 * @return SocialNetworkUser[]
	 */
	public function SocialNetworkFriends ($user, $count, $offset) {
		// TODO: Implement SocialNetworkFriends() method.
	}

	/**
	 * @note if Twitter responds with error - type a placeholder into Callback URL field in the application settings
	 *
	 * @param string $method = ''
	 * @param string $url = ''
	 * @param array $params = []
	 *
	 * @return QuarkKeyValuePair
	 */
	private function _authorization ($method = '', $url = '', $params = []) {
		$header = array();
		$now = QuarkDate::Now();

		$params['oauth_signature_method'] = 'HMAC-SHA1';
		$params['oauth_version'] = '1.0';
		$params['oauth_timestamp'] = $now->Timestamp();
		$params['oauth_nonce'] = Quark::GuID();
		$params['oauth_consumer_key'] = $this->_appId;

		if ($this->_callback)
			$params['oauth_callback'] = $this->_callback;

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
}