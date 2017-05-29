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
use Quark\Extensions\OAuth\OAuthAPIException;

use Quark\Extensions\SocialNetwork\IQuarkSocialNetworkProvider;
use Quark\Extensions\SocialNetwork\SocialNetwork;
use Quark\Extensions\SocialNetwork\SocialNetworkUser;

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

	const AGGREGATE_COUNT = 42;
	const AGGREGATE_CURSOR = '-1';

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
	 * @var string $_cursor = self::AGGREGATE_CURSOR
	 */
	private $_cursor = self::AGGREGATE_CURSOR;

	/**
	 * @return string
	 */
	public function &Cursor () {
		return $this->_cursor;
	}

	/**
	 * @param OAuthToken $token
	 *
	 * @return IQuarkOAuthConsumer
	 */
	public function OAuthConsumer (OAuthToken $token) {
		$this->_token = $token;

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

		$login = $this->OAuthAPI(
			'/oauth/request_token',
			QuarkDTO::ForPOST(new QuarkFormIOProcessor()),
			new QuarkDTO(new QuarkFormIOProcessor())
		);

		return isset($login->oauth_token) ? self::URL_API . '/oauth/authenticate?oauth_token=' . $login->oauth_token : null;
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
		if (!isset($request->oauth_token)) return null;
		if (!isset($request->oauth_verifier)) return null;

		$this->_token = new OAuthToken();
		$this->_token->access_token = $request->oauth_token;

		$req = QuarkDTO::ForPOST(new QuarkFormIOProcessor());
		$req->Data(array('oauth_verifier' => $request->oauth_verifier));

		$token = $this->OAuthAPI('/oauth/access_token', $req, new QuarkDTO(new QuarkFormIOProcessor()));

		if (!isset($token->oauth_token)) return null;
		if (!isset($token->oauth_token_secret)) return null;

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
	 * @throws OAuthAPIException
	 */
	public function OAuthAPI ($url = '', QuarkDTO $request = null, QuarkDTO $response = null) {
		if ($request == null) $request = QuarkDTO::ForGET(new QuarkFormIOProcessor());
		if ($response == null) $response = new QuarkDTO(new QuarkJSONIOProcessor());

		$request->Authorization($this->_authorization($request->Method(), self::URL_API . $url));

		$api = QuarkHTTPClient::To(self::URL_API . $url, $request, $response);

		if (isset($api->errors))
			throw new OAuthAPIException($request, $response);

		return $api;
	}

	/**
	 * @param $item
	 * @param bool $photo = false
	 *
	 * @return SocialNetworkUser
	 */
	private static function _user ($item, $photo = false) {
		$user = new SocialNetworkUser($item->id, $item->name);

		$user->PhotoFromLink(isset($item->profile_image_url_https) ? $item->profile_image_url_https : '', $photo);
		$user->Location($item->location);
		$user->Page(self::URL_BASE . $item->screen_name);
		$user->RegisteredAt(QuarkDate::GMTOf($item->created_at));
		$user->Bio($item->description);

		if (isset($item->email))
			$user->Email($item->email);

		if (isset($item->birthday))
			$user->BirthdayByDate('m/d/Y', $item->birthday);

		return $user;
	}

	/**
	 * @param string $user
	 *
	 * @return SocialNetworkUser
	 */
	public function SocialNetworkUser ($user) {
		$response = $this->OAuthAPI(
			$user == ''
				? '/1.1/account/verify_credentials.json'
				: '/1.1/users/lookup.json?user_id=' . $user,
			QuarkDTO::ForGET(new QuarkFormIOProcessor()),
			new QuarkDTO(new QuarkJSONIOProcessor())
		);

		if (is_array($response->Data())) $response = $response->Data();
		else $response = array($response);

		return sizeof($response) == 0 || $response[0] == null ? null : self::_user($response[0]);
	}

	/**
	 * @param string $user
	 * @param int $count
	 * @param int $offset
	 *
	 * @return SocialNetworkUser[]
	 */
	public function SocialNetworkFriends ($user, $count, $offset) {
		// TODO:
		// screen_name=twitterapi
		// skip_status=true
		// include_user_entities=false

		$response = $this->OAuthAPI(
			//'/1.1/friends/list.json?count=' . ($count ? $count : self::AGGREGATE_COUNT) . '&cursor=' . ($offset ? $offset : $this->_cursor) . '&user_id=' . $user . '',
			'/1.1/friends/list.json?cursor=-1&screen_name=twitterapi&skip_status=true&include_user_entities=false',
			QuarkDTO::ForGET(new QuarkFormIOProcessor()),
			new QuarkDTO(new QuarkJSONIOProcessor())
		);

		if (!isset($response->users) || !is_array($response->users)) return array();

		$friends = array();

		foreach ($response->users as $item)
			$friends[] = self::_user($item);

		return $friends;
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