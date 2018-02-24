<?php
namespace Quark\Extensions\SocialNetwork\Providers;

use Quark\Quark;
use Quark\QuarkURI;
use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkModel;
use Quark\QuarkDate;

use Quark\Extensions\OAuth\IQuarkOAuthConsumer;
use Quark\Extensions\OAuth\IQuarkOAuthProvider;
use Quark\Extensions\OAuth\OAuthToken;
use Quark\Extensions\OAuth\OAuthConfig;
use Quark\Extensions\OAuth\OAuthAPIException;

use Quark\Extensions\SocialNetwork\IQuarkSocialNetworkProvider;
use Quark\Extensions\SocialNetwork\SocialNetwork;
use Quark\Extensions\SocialNetwork\SocialNetworkUser;
use Quark\Extensions\SocialNetwork\SocialNetworkPost;
use Quark\Extensions\SocialNetwork\SocialNetworkPublishingChannel;

/**
 * Class VKontakte
 *
 * @package Quark\Extensions\SocialNetwork\Providers
 */
class VKontakte implements IQuarkOAuthProvider, IQuarkSocialNetworkProvider {
	const URL_BASE = 'http://vk.com/id';
	const URL_OAUTH = 'https://oauth.vk.com';
	const URL_API = 'https://api.vk.com/method';

	const API_VERSION = '5.29';

	const CURRENT_USER = '';

	const PERMISSION_ID = 'uid';
	const PERMISSION_NAME = '';
	const PERMISSION_PICTURE = 'photo_max_orig';
	const PERMISSION_GENDER = 'sex';
	const PERMISSION_LINK = 'link';

	const PERMISSION_EMAIL = 'email';
	const PERMISSION_BIRTHDAY = 'bdate';

	const PERMISSION_NOTIFY = 'notify';
	const PERMISSION_FRIENDS = 'friends';
	const PERMISSION_PHOTOS = 'photos';
	const PERMISSION_AUDIO = 'audio';
	const PERMISSION_VIDEO = 'video';
	const PERMISSION_DOCS = 'docs';
	const PERMISSION_NOTES = 'notes';
	const PERMISSION_PAGES = 'pages';
	const PERMISSION_STATUS = 'status';
	const PERMISSION_OFFERS = 'offers';
	const PERMISSION_QUESTIONS = 'questions';
	const PERMISSION_WALL = 'wall';
	const PERMISSION_GROUPS = 'groups';
	const PERMISSION_MESSAGES = 'messages';
	const PERMISSION_NOTIFICATIONS = 'notifications';
	const PERMISSION_STATS = 'stats';
	const PERMISSION_ADS = 'ads';

	const PERMISSION_OFFLINE = 'offline';
	const PERMISSION_NOHTTPS = 'nohttps';

	/**
	 * @var string $_appId = ''
	 */
	private $_appId = '';

	/**
	 * @var string $_appSecret = ''
	 */
	private $_appSecret = '';

	/**
	 * @var OAuthToken $_token
	 */
	private $_token;

	/**
	 * @var string[] $_gender
	 */
	private static $_gender = array(
		SocialNetworkUser::GENDER_FEMALE,
		SocialNetworkUser::GENDER_MALE
	);

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
		return QuarkURI::Build(self::URL_OAUTH . '/authorize', array(
			'client_id' => $this->_appId,
			'redirect_uri' => $redirect,
			'state' => Quark::GuID(),
			'scope' => implode(',', (array)$scope),
			'v' => self::API_VERSION,
			'response_type' => OAuthConfig::RESPONSE_CODE
		));
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
		if (!isset($request->code)) return null;

		$req = QuarkDTO::ForGET(new QuarkFormIOProcessor());
		$req->URIParams(array(
			'client_id' => $this->_appId,
			'client_secret' => $this->_appSecret,
			'redirect_uri' => $redirect,
			'code' => $request->code
		));

		$res = new QuarkDTO(new QuarkJSONIOProcessor());

		$api = $this->OAuthAPI('/access_token', $req, $res, self::URL_OAUTH);

		return $api == null ? null : new QuarkModel(new OAuthToken(), $api->Data());
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

		if ($this->_token != null)
			$request->URIInit(array('access_token' => $this->_token->access_token));

		$api = QuarkHTTPClient::To($base . $url, $request, $response);

		if (isset($api->error))
			throw new OAuthAPIException($request, $response);

		return $api;
	}

	/**
	 * @param string[] $fields
	 *
	 * @return string
	 */
	private static function _fields ($fields = []) {
		return implode(',', $fields != null ? $fields : array(
			self::PERMISSION_GENDER,
			self::PERMISSION_PICTURE,
			self::PERMISSION_BIRTHDAY,
			self::PERMISSION_EMAIL
		));
	}

	/**
	 * @param $item
	 * @param bool $photo = false
	 *
	 * @return SocialNetworkUser
	 */
	private static function _user ($item, $photo = false) {
		if (!$item) return null;

		$user = new SocialNetworkUser($item->uid, $item->first_name . ' ' . $item->last_name);

		$user->PhotoFromLink($item->photo_max_orig, $photo);
		$user->Gender(isset(self::$_gender[$item->sex]) ? self::$_gender[$item->sex] : SocialNetworkUser::GENDER_UNKNOWN);
		$user->Page(self::URL_BASE . $item->uid);

		if (isset($item->email))
			$user->Email($item->email);

		if (isset($item->bdate)) {
			$date = explode('.', $item->bdate);
			$out = array();

			foreach ($date as $component)
				$out[] = (strlen($component == 1) ? '0' : '') . $component;

			if (sizeof($out) == 2)
				$out[] = QuarkDate::UNKNOWN_YEAR;

			$user->BirthdayByDate('d.m.Y', implode('.', $out), 'd.m');
		}

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
		return $user == SocialNetwork::CURRENT_USER ? self::CURRENT_USER : $user;
	}

	/**
	 * @param int $count
	 *
	 * @return int
	 */
	public function SocialNetworkParameterFriendsCount ($count) {
		return $count == SocialNetwork::FRIENDS_ALL ? 0 : $count;
	}

	/**
	 * @param string $user
	 * @param string[] $fields = []
	 *
	 * @return SocialNetworkUser
	 */
	public function SocialNetworkUser ($user, $fields = []) {
		$request = QuarkDTO::ForGET(new QuarkFormIOProcessor());
		$request->URIParams(array(
			'fields' => self::_fields($fields),
			'user_ids' => $user,
			'scope' => 'email'
		));

		$response = $this->OAuthAPI('/users.get', $request);

		if ($response == null || !isset($response->response) || !is_array($response->response)) return null;

		return self::_user(isset($response->response[0]) ? $response->response[0] : null);
	}

	/**
	 * @param string $user
	 * @param int $count
	 * @param int $offset
	 * @param string[] $fields = []
	 *
	 * @return SocialNetworkUser[]
	 */
	public function SocialNetworkFriends ($user, $count, $offset, $fields = []) {
		$request = QuarkDTO::ForGET(new QuarkFormIOProcessor());
		$request->URIParams(array(
			'user_id' => $user,
			'scope' => 'email',
			'fields' => self::_fields($fields),
			'count' => $count,
			'offset' => $offset
		));

		$response = $this->OAuthAPI('/friends.get', $request);

		if ($response == null || !is_array($response->response)) return array();

		$friends = array();

		foreach ($response->response as $item)
			$friends[] = self::_user($item);

		return $friends;
	}

	/**
	 * @param SocialNetworkPost $post
	 * @param bool $preview
	 *
	 * @return SocialNetworkPost
	 */
	public function SocialNetworkPublish (SocialNetworkPost $post, $preview) {
		// TODO: Implement SocialNetworkPublish() method.
	}

	/**
	 * @param string $user
	 *
	 * @return SocialNetworkPublishingChannel[]
	 */
	public function SocialNetworkPublishingChannels ($user) {
		// TODO: Implement SocialNetworkPublishingChannels() method.
	}

	/**
	 * Limit of the post length
	 *
	 * @return int
	 */
	public function SocialNetworkPublishingLengthLimit () {
		return SocialNetwork::PUBLISHING_LIMIT_NONE;
	}
}