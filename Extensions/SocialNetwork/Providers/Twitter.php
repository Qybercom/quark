<?php
namespace Quark\Extensions\SocialNetwork\Providers;

use Quark\Quark;
use Quark\QuarkDate;
use Quark\QuarkDTO;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkKeyValuePair;

use Quark\Extensions\SocialNetwork\IQuarkSocialNetworkProvider;
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
class Twitter implements IQuarkSocialNetworkProvider {
	const BASE_DOMAIN = 'https://twitter.com/';
	const BASE_URL = 'https://api.twitter.com';

	/**
	 * @var string $_appId
	 */
	private $_appId = '';

	/**
	 * @var string $_appSecret = ''
	 */
	private $_appSecret = '';

	/**
	 * @var string $_token = ''
	 */
	private $_token = '';

	/**
	 * @var string $_secret = ''
	 */
	private $_secret = '';

	/**
	 * @var string $_callback = ''
	 */
	private $_callback = '';

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

		if ($this->_token)
			$params['oauth_token'] = $this->_token;

        // Parameters are sorted by name, using lexicographical byte value ordering.
        // Ref: Spec: 9.1.1 (1)
        uksort($params, 'strcmp');

		$_sign = array();

		foreach ($params as $key => $value) {
			$header[] = rawurlencode($key) . '="' . rawurlencode($value) .'"';
			$_sign[] = rawurlencode($key) . '=' . rawurlencode($value);
		}

		$key = rawurlencode($this->_appSecret) . '&' . rawurlencode($this->_secret);
		$data = rawurlencode($method) . '&' . rawurlencode($url) . '&' . rawurlencode(implode('&', $_sign));

		$sign = base64_encode(hash_hmac('sha1', $data, $key, true));
		$header[] = 'oauth_signature="' . rawurlencode($sign) . '"';

		return new QuarkKeyValuePair('OAuth', implode(',', $header));
	}

	/**
	 * @return string
	 */
	public function Name () {
		return 'Twitter';
	}

	/**
	 * @param string $appId
	 * @param string $appSecret
	 *
	 * @return mixed
	 */
	public function SocialNetworkApplication ($appId, $appSecret) {
		$this->_appId = $appId;
		$this->_appSecret = $appSecret;
	}

	/**
	 * @param string $to
	 * @param string[] $permissions
	 *
	 * @return string
	 */
	public function LoginURL ($to, $permissions = []) {
		$this->_callback = $to;

		$login = $this->OAuthAPI(QuarkDTO::METHOD_POST, '/oauth/request_token');

		return 'https://api.twitter.com/oauth/authenticate?oauth_token=' . $login->oauth_token;
	}

	/**
	 * @param string $to
	 *
	 * @return string
	 */
	public function LogoutURL ($to) {
		// TODO: Implement LogoutURL() method.
	}

	/**
	 * @param QuarkDTO $request
	 * @param string $to
	 *
	 * @return string
	 */
	public function SessionFromRedirect (QuarkDTO $request, $to) {
		$this->_token = $request->oauth_token;

		$token = $this->OAuthAPI(QuarkDTO::METHOD_POST, '/oauth/access_token', array(
			'oauth_verifier' => $request->oauth_verifier
		));

		$this->_token = $token->oauth_token;
		$this->_secret = $token->oauth_token_secret;

		return base64_encode(json_encode(array(
			'token' => $token->oauth_token,
			'secret' => $token->oauth_token_secret
		)));
	}

	/**
	 * @param string $token
	 *
	 * @return string
	 */
	public function SessionFromToken ($token) {
		$json = json_decode(base64_decode($token));

		$this->_token = $json->token;
		$this->_secret = $json->secret;

		return $token;
	}

	/**
	 * @return string
	 */
	public function CurrentUser () {
		return '';
	}

	/**
	 * @param string $method = ''
	 * @param string $url = ''
	 * @param array  $data = []
	 * @param string $base = self::BASE_URL
	 *
	 * @return QuarkDTO|\stdClass
	 */
	public function OAuthAPI ($method = '', $url = '', $data = [], $base = self::BASE_URL) {
		$request = new QuarkDTO(new QuarkFormIOProcessor());
		$request->Method($method);
		$request->Authorization($this->_authorization($request->Method(), $base . $url));

		$get = $method == QuarkDTO::METHOD_GET;
		if (!$get) $request->Data($data);

		$response = new QuarkDTO(new QuarkFormIOProcessor());

		$out = QuarkHTTPClient::To($base . $url, $request, $response);

		return $out;
	}

	/**
	 * @param string $method = ''
	 * @param string $url = ''
	 * @param array  $data = []
	 * @param string $base = self::BASE_URL
	 *
	 * @return QuarkDTO|\stdClass
	 */
	public function API ($method = '', $url = '', $data = [], $base = self::BASE_URL) {
		$request = new QuarkDTO(new QuarkFormIOProcessor());
		$request->Method($method);
		$request->Authorization($this->_authorization($request->Method(), $base . $url));

		$get = $method == QuarkDTO::METHOD_GET;
		if (!$get) $request->Data($data);

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		$out = QuarkHTTPClient::To($base . $url, $request, $response);

		return $out;
	}

	/**
	 * @param string $user
	 * @param string[] $fields
	 *
	 * @return SocialNetworkUser
	 */
	public function Profile ($user, $fields) {
		$response = $this->API(QuarkDTO::METHOD_GET, ($user == ''
			? '/1.1/account/verify_credentials.json'
			: '/1.1/users/lookup.json?user_id=' . $user));

		if (is_array($response->Data())) $response = $response->Data();
		else $response = array($response);

		if (sizeof($response) == 0 || $response[0] == null) return null;
		$response = $response[0];

		$profile = new SocialNetworkUser($response->id, $response->name);

		$profile->PhotoFromLink($response->profile_image_url_https);
		$profile->Page(self::BASE_DOMAIN . $response->screen_name);

		return $profile;
	}

	/**
	 * @param string $user
	 * @param string[] $fields
	 * @param int $count
	 * @param int $offset
	 *
	 * @return SocialNetworkUser[]
	 */
	public function Friends ($user, $fields, $count, $offset) {
		// TODO: Implement Friends() method.
	}
}