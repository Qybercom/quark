<?php
namespace Quark\Extensions\SocialNetwork\Providers;

use Quark\QuarkArchException;
use Quark\QuarkDate;
use Quark\QuarkDTO;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkModel;

use Quark\Extensions\OAuth\IQuarkOAuthConsumer;
use Quark\Extensions\OAuth\IQuarkOAuthProvider;
use Quark\Extensions\OAuth\OAuthToken;
use Quark\Extensions\OAuth\OAuthAPIException;
use Quark\Extensions\OAuth\OAuthProviderBehavior;

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
 * http://kagan.mactane.org/blog/2009/09/22/what-characters-are-allowed-in-twitter-usernames/comment-page-1/
 * https://support.twitter.com/articles/101299
 *
 * @package Quark\Extensions\SocialNetwork\Providers
 */
class Twitter implements IQuarkOAuthProvider, IQuarkSocialNetworkProvider {
	const URL_BASE = 'https://twitter.com/';
	const URL_API = 'https://api.twitter.com';
	const URL_STREAM_PUBLIC = 'https://stream.twitter.com/1.1/statuses';

	const AGGREGATE_COUNT = 42;
	const AGGREGATE_CURSOR = '-1';

	use OAuthProviderBehavior;

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
		$login = $this->OAuth1_0a_RequestToken($redirect, '/oauth/request_token', self::URL_API);

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
		return $this->OAuth1_0a_TokenFromRequest($request, '/oauth/access_token', self::URL_API);
	}

	/**
	 * @param string $url = ''
	 * @param QuarkDTO $request = null
	 * @param QuarkDTO $response = null
	 * @param string $base = self::URL_API
	 *
	 * @return QuarkDTO|null
	 *
	 * @throws OAuthAPIException
	 */
	public function OAuthAPI ($url = '', QuarkDTO $request = null, QuarkDTO $response = null, $base = self::URL_API) {
		if ($request == null) $request = QuarkDTO::ForGET(new QuarkFormIOProcessor());
		if ($response == null) $response = new QuarkDTO(new QuarkJSONIOProcessor());

		$request->Authorization($this->OAuth1_0a_AuthorizationHeader($request->Method(), $base . $url));

		$api = QuarkHTTPClient::To($base . $url, $request, $response);

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
		$user->Username($item->screen_name);
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
		//
		// NEED INVESTIGATING - TWITTER RETURN ERROR ON WELL-FORMED REQUEST
		//
		// screen_name=twitterapi
		// skip_status=true
		// include_user_entities=false

		$response = $this->OAuthAPI(
			'/1.1/friends/list.json?count=' . ($count ? $count : self::AGGREGATE_COUNT) . '&cursor=' . ($offset ? $offset : $this->_cursor) . '&user_id=' . $user . '',
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
	 * @param array|object $filter
	 * @param callable $incoming
	 *
	 * @return QuarkHTTPClient
	 */
	public function TwitterStreaming ($filter = [], callable $incoming) {
		$url = self::URL_STREAM_PUBLIC . '/filter.json';

		$request = QuarkDTO::ForPOST(new QuarkFormIOProcessor());
		$request->Protocol(QuarkDTO::HTTP_VERSION_1_1);
		$request->Authorization($this->OAuth1_0a_AuthorizationHeader($request->Method(), $url, $filter));
		$request->Data($filter);

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		$stream = QuarkHTTPClient::AsyncTo($url, $request, $response);

		$stream->On(QuarkHTTPClient::EVENT_ASYNC_ERROR, function ($request, $response) {
			throw new QuarkArchException('[SocialNetwork.Twitter] StreamingAPI error. Details: ' . print_r($request, true) . print_r($response, true));
		});

		return $stream ? $stream->AsyncData($incoming) : null;
	}
}