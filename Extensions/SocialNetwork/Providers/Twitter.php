<?php
namespace Quark\Extensions\SocialNetwork\Providers;

use Quark\QuarkArchException;
use Quark\QuarkDate;
use Quark\QuarkDTO;
use Quark\QuarkFile;
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
use Quark\Extensions\SocialNetwork\SocialNetworkPost;

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
	const URL_MEDIA_UPLOAD = 'https://upload.twitter.com/1.1/media/upload.json';

	const MEDIA_COMMAND_INIT = 'INIT';
	const MEDIA_COMMAND_APPEND = 'APPEND';
	const MEDIA_COMMAND_FINALIZE = 'FINALIZE';

	const AGGREGATE_COUNT = 42;
	const AGGREGATE_CURSOR = '-1';

	const CRITERIA_ID = 'user_id';
	const CRITERIA_USERNAME = 'screen_name';

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
	 * @param bool $dataSign = true
	 *
	 * @return QuarkDTO|null
	 *
	 * @throws OAuthAPIException
	 */
	public function OAuthAPI ($url = '', QuarkDTO $request = null, QuarkDTO $response = null, $base = self::URL_API, $dataSign = true) {
		if ($request == null) $request = QuarkDTO::ForGET(new QuarkFormIOProcessor());
		if ($response == null) $response = new QuarkDTO(new QuarkJSONIOProcessor());

		$data = $request->Data();
		$request->Authorization($this->OAuth1_0a_AuthorizationHeader($request->Method(), $base . $url, is_scalar($data) || !$dataSign ? array() : (array)$data));

		if ($request->Method() == QuarkDTO::METHOD_GET) {
			$query = $request->Processor()->Encode($request->Data());
			if ($query) $url .= '?' . $query;

			$request->Data('');
		}

		$api = QuarkHTTPClient::To($base . $url, $request, $response);

		if (isset($api->errors) || isset($api->error))
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
	 * @param array|object $data
	 * @param bool $photo = false
	 *
	 * @return SocialNetworkUser
	 */
	public function SocialNetworkProfile ($data, $photo = false) {
		return self::_user($data, $photo);
	}

	/**
	 * @param string $user
	 *
	 * @return string
	 */
	public function SocialNetworkParameterUser ($user) {
		return $user;
	}

	/**
	 * @param int $count
	 *
	 * @return int
	 */
	public function SocialNetworkParameterFriendsCount ($count) {
		return $count == SocialNetwork::FRIENDS_ALL ? self::AGGREGATE_COUNT : $count;
	}

	/**
	 * @param string $user
	 *
	 * @return SocialNetworkUser
	 */
	public function SocialNetworkUser ($user) {
		$response = $this->OAuthAPI(
			$user == SocialNetwork::CURRENT_USER
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
		if ($count == SocialNetwork::FRIENDS_ALL)
			$count = 0;

		$request = QuarkDTO::ForGET(new QuarkFormIOProcessor());
		$request->Data(array(
			'count' => $count,
			'cursor' => $offset ? $offset : $this->_cursor,
			'user_id' => $user
		));

		$response = $this->OAuthAPI(
			'/1.1/friends/list.json',
			$request,
			new QuarkDTO(new QuarkJSONIOProcessor())
		);

		if (!isset($response->users) || !is_array($response->users)) return array();

		$friends = array();

		foreach ($response->users as $item)
			$friends[] = self::_user($item);

		return $friends;
	}

	/**
	 * @param SocialNetworkPost $post
	 *
	 * https://dev.twitter.com/rest/reference/post/statuses/update
	 * https://github.com/abraham/twitteroauth/issues/387
	 *
	 * @return SocialNetworkPost
	 */
	public function SocialNetworkPublish (SocialNetworkPost $post) {
		$media = array();
		$attachments = $post->Attachments();

		foreach ($attachments as $i => &$attachment) {
			$id = $this->TwitterMediaUpload($attachment);

			if ($id) $media[] = $id;
		}

		$request = QuarkDTO::ForPOST(new QuarkFormIOProcessor());
		$request->Data(array(
			'status' => $post->Content(),
			'in_reply_to_status_id' => $post->Reply(),
			'possibly_sensitive' => $post->Sensitive() ? 'true' : 'false',
			'media_ids' => implode(',', $media)
		));

		$response = $this->OAuthAPI(
			'/1.1/statuses/update.json',
			$request,
			new QuarkDTO(new QuarkJSONIOProcessor())
		);

		if (!isset($response->id_str))
			return null;

		$post->ID($response->id_str);

		return $post;
	}

	/**
	 * @param string[] $users = []
	 * @param string $criteria = self::CRITERIA_USERNAME
	 *
	 * @return SocialNetworkUser[]
	 *
	 * @throws OAuthAPIException
	 */
	public function Profiles ($users = [], $criteria = self::CRITERIA_USERNAME) {
		try {
			if (sizeof($users) == 0) return array();

			$request = QuarkDTO::ForGET(new QuarkFormIOProcessor());
			$request->Data(array(
				$criteria => implode(',', $users)
			));

			$response = $this->OAuthAPI('/1.1/users/lookup.json',
				$request,
				new QuarkDTO(new QuarkJSONIOProcessor())
			);

			$out = array();
			$data = $response->Data();

			if (!is_array($data)) return array();

			foreach ($data as $i => &$item)
				$out[] = self::_user($item);

			return $out;
		}
		catch (OAuthAPIException $e) {
			return $this->_oauth_error($e, 'Profiles', 'API error', array());
		}
	}

	/**
	 * @param array|object $params = []
	 * @param callable $incoming = null
	 * @param bool $filter = true
	 *
	 * @return QuarkHTTPClient
	 */
	public function TwitterStreaming ($params = [], callable $incoming = null, $filter = true) {
		$url = self::URL_STREAM_PUBLIC . '/filter.json';

		$request = QuarkDTO::ForPOST(new QuarkFormIOProcessor());
		$request->Protocol(QuarkDTO::HTTP_VERSION_1_1);
		$request->Authorization($this->OAuth1_0a_AuthorizationHeader($request->Method(), $url, (array)$params));
		$request->Data((array)$params);

		$response = new QuarkDTO(new QuarkJSONIOProcessor());
		$response->RawProcessor(function ($data) {
			return preg_replace('#\r\n[a-zA-Z0-9]{1,5}\r\n#Uis', '', "\r\n" . $data);
		});

		$stream = QuarkHTTPClient::AsyncTo($url, $request, $response);
		if ($stream == null) return null;

		$stream->On(QuarkHTTPClient::EVENT_ASYNC_ERROR, function ($request, $response) {
			throw new QuarkArchException('[SocialNetwork.Twitter] StreamingAPI error. Details: ' . print_r($request, true) . print_r($response, true));
		});

		$last = '';

		return $stream->AsyncData(!$filter || !$incoming ? $incoming : function (QuarkDTO $data) use (&$last, &$incoming) {
			if (!isset($data->text) || !isset($data->user->name)) return;
			if (!isset($data->id) || !isset($data->created_at)) return;
			if ($last == $data->id) return;

			$last = $data->id;
			$incoming($data);
		});
	}

	/**
	 * @param $command
	 * @param array|object $payload = []
	 * @param bool $sign = true
	 *
	 * @return QuarkDTO
	 *
	 * @throws QuarkArchException
	 */
	public function TwitterMediaAPI ($command, $payload = [], $sign = true) {
		$payload = (array)$payload;
		$payload['command'] = $command;

		$request = QuarkDTO::ForPOST(new QuarkFormIOProcessor());
		$request->Data($payload);

		try {
			return $this->OAuthAPI('', $request, new QuarkDTO(new QuarkJSONIOProcessor()), self::URL_MEDIA_UPLOAD, $sign);
		}
		catch (OAuthAPIException $e) {
			throw new QuarkArchException('[SocialNetwork.Twitter] MediaAPI error. Details: ' . print_r($e->Request(), true) . print_r($e->Response(), true));
		}
	}

	/**
	 * @param QuarkFile $file = null
	 *
	 * @return string
	 */
	public function TwitterMediaUpload (QuarkFile $file = null) {
		if ($file == null) return null;

		$id = $this->TwitterMediaAPI(self::MEDIA_COMMAND_INIT, array(
			'total_bytes' => $file->size,
			'media_type' => $file->type
		));

		if (!$id || !isset($id->media_id_string)) return null;

		$chunk = $this->TwitterMediaAPI(self::MEDIA_COMMAND_APPEND, array(
			'media_id' => $id->media_id_string,
			'media' => $file,
			'segment_index' => 0 // TODO: refactor in order to support videos and animated GIFs
		), false);

		if (substr($chunk->StatusCode(), 0, 1) != 2) return null;

		$id = $this->TwitterMediaAPI(self::MEDIA_COMMAND_FINALIZE, array(
			'media_id' => $id->media_id_string,
		));

		return $id && isset($id->media_id_string) ? $id->media_id_string : null;
	}
}