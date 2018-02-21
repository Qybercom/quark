<?php
namespace Quark\Extensions\SocialNetwork\Providers;

use Quark\Quark;
use Quark\QuarkObject;
use Quark\QuarkURI;
use Quark\QuarkDTO;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkHTTPClient;
use Quark\QuarkModel;

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
 * Class Odnoklassniki
 *
 * @package Quark\Extensions\SocialNetwork\Providers
 */
class Odnoklassniki implements IQuarkOAuthProvider, IQuarkSocialNetworkProvider {
	const URL_OAUTH = 'https://connect.ok.ru/oauth/authorize';
	const URL_API = 'https://api.ok.ru';
	const URL_PROFILE = 'https://ok.ru/profile/';

	const GENDER_MALE = 'male';
	const GENDER_FEMALE = 'female';

	const SCOPE_VALUABLE_ACCESS = 'VALUABLE_ACCESS';
	const SCOPE_LONG_ACCESS_TOKEN = 'LONG_ACCESS_TOKEN';
	const SCOPE_PHOTO_CONTENT = 'PHOTO_CONTENT';
	const SCOPE_GROUP_CONTENT = 'GROUP_CONTENT';
	const SCOPE_VIDEO_CONTENT = 'VIDEO_CONTENT';
	const SCOPE_APP_INVITE = 'APP_INVITE';
	const SCOPE_GET_EMAIL = 'GET_EMAIL';

	const FIELD_ACCESSIBLE = 'ACCESSIBLE';
	const FIELD_AGE = 'AGE';
	const FIELD_ALLOWS_ANONYM_ACCESS = 'ALLOWS_ANONYM_ACCESS';
	const FIELD_ALLOWS_MESSAGING_ONLY_FOR_FRIENDS = 'ALLOWS_MESSAGING_ONLY_FOR_FRIENDS';
	const FIELD_BECOME_VIP_ALLOWED = 'BECOME_VIP_ALLOWED';
	const FIELD_BIRTHDAY = 'BIRTHDAY';
	const FIELD_BLOCKED = 'BLOCKED';
	const FIELD_BLOCKS = 'BLOCKS';
	const FIELD_CAN_VCALL = 'CAN_VCALL';
	const FIELD_CAN_VMAIL = 'CAN_VMAIL';
	const FIELD_COMMON_FRIENDS_COUNT = 'COMMON_FRIENDS_COUNT';
	const FIELD_CURRENT_LOCATION = 'CURRENT_LOCATION';
	const FIELD_CURRENT_STATUS = 'CURRENT_STATUS';
	const FIELD_CURRENT_STATUS_DATE = 'CURRENT_STATUS_DATE';
	const FIELD_CURRENT_STATUS_DATE_MS = 'CURRENT_STATUS_DATE_MS';
	const FIELD_CURRENT_STATUS_ID = 'CURRENT_STATUS_ID';
	const FIELD_CURRENT_STATUS_TRACK_ID = 'CURRENT_STATUS_TRACK_ID';
	const FIELD_EMAIL = 'EMAIL';
	const FIELD_FIRST_NAME = 'FIRST_NAME';
	const FIELD_FRIEND = 'FRIEND';
	const FIELD_FRIEND_INVITATION = 'FRIEND_INVITATION';
	const FIELD_FRIEND_INVITE_ALLOWED = 'FRIEND_INVITE_ALLOWED';
	const FIELD_GENDER = 'GENDER';
	const FIELD_GROUP_INVITE_ALLOWED = 'GROUP_INVITE_ALLOWED';
	const FIELD_HAS_EMAIL = 'HAS_EMAIL';
	const FIELD_HAS_SERVICE_INVISIBLE = 'HAS_SERVICE_INVISIBLE';
	const FIELD_INTERNAL_PIC_ALLOW_EMPTY = 'INTERNAL_PIC_ALLOW_EMPTY';
	const FIELD_INVITED_BY_FRIEND = 'INVITED_BY_FRIEND';
	const FIELD_IS_ACTIVATED = 'IS_ACTIVATED';
	const FIELD_LAST_NAME = 'LAST_NAME';
	const FIELD_LAST_ONLINE = 'LAST_ONLINE';
	const FIELD_LAST_ONLINE_MS = 'LAST_ONLINE_MS';
	const FIELD_LOCALE = 'LOCALE';
	const FIELD_LOCATION = 'LOCATION';
	const FIELD_MODIFIED_MS = 'MODIFIED_MS';
	const FIELD_NAME = 'NAME';
	const FIELD_ODKL_BLOCK_REASON = 'ODKL_BLOCK_REASON';
	const FIELD_ODKL_EMAIL = 'ODKL_EMAIL';
	const FIELD_ODKL_LOGIN = 'ODKL_LOGIN';
	const FIELD_ODKL_MOBILE = 'ODKL_MOBILE';
	const FIELD_ODKL_MOBILE_STATUS = 'ODKL_MOBILE_STATUS';
	const FIELD_ODKL_USER_OPTIONS = 'ODKL_USER_OPTIONS';
	const FIELD_ODKL_USER_STATUS = 'ODKL_USER_STATUS';
	const FIELD_ODKL_VOTING = 'ODKL_VOTING';
	const FIELD_ONLINE = 'ONLINE';
	const FIELD_PHOTO_ID = 'PHOTO_ID';
	const FIELD_PIC1024X768 = 'PIC1024X768';
	const FIELD_PIC128MAX = 'PIC128MAX';
	const FIELD_PIC128X128 = 'PIC128X128';
	const FIELD_PIC180MIN = 'PIC180MIN';
	const FIELD_PIC190X190 = 'PIC190X190';
	const FIELD_PIC224X224 = 'PIC224X224';
	const FIELD_PIC240MIN = 'PIC240MIN';
	const FIELD_PIC288X288 = 'PIC288X288';
	const FIELD_PIC320MIN = 'PIC320MIN';
	const FIELD_PIC50X50 = 'PIC50X50';
	const FIELD_PIC600X600 = 'PIC600X600';
	const FIELD_PIC640X480 = 'PIC640X480';
	const FIELD_PIC_1 = 'PIC_1';
	const FIELD_PIC_2 = 'PIC_2';
	const FIELD_PIC_3 = 'PIC_3';
	const FIELD_PIC_4 = 'PIC_4';
	const FIELD_PIC_5 = 'PIC_5';
	const FIELD_PIC_BASE = 'PIC_BASE';
	const FIELD_PIC_FULL = 'PIC_FULL';
	const FIELD_PIC_MAX = 'PIC_MAX';
	const FIELD_PREMIUM = 'PREMIUM';
	const FIELD_PRESENTS = 'PRESENTS';
	const FIELD_PRIVATE = 'PRIVATE';
	const FIELD_PYMK_PIC224X224 = 'PYMK_PIC224X224';
	const FIELD_PYMK_PIC288X288 = 'PYMK_PIC288X288';
	const FIELD_PYMK_PIC600X600 = 'PYMK_PIC600X600';
	const FIELD_PYMK_PIC_FULL = 'PYMK_PIC_FULL';
	const FIELD_REF = 'REF';
	const FIELD_REGISTERED_DATE = 'REGISTERED_DATE';
	const FIELD_REGISTERED_DATE_MS = 'REGISTERED_DATE_MS';
	const FIELD_RELATIONS = 'RELATIONS';
	const FIELD_RELATIONSHIP = 'RELATIONSHIP';
	const FIELD_SEND_MESSAGE_ALLOWED = 'SEND_MESSAGE_ALLOWED';
	const FIELD_SHOW_LOCK = 'SHOW_LOCK';
	const FIELD_UID = 'UID';
	const FIELD_URL_CHAT = 'URL_CHAT';
	const FIELD_URL_CHAT_MOBILE = 'URL_CHAT_MOBILE';
	const FIELD_URL_PROFILE = 'URL_PROFILE';
	const FIELD_URL_PROFILE_MOBILE = 'URL_PROFILE_MOBILE';
	const FIELD_VIP = 'VIP';

	/**
	 * @var string $_appId = ''
	 */
	private $_appId = '';

	/**
	 * @var string $_appPublic = ''
	 */
	private $_appPublic = '';

	/**
	 * @var string $_appSecret = ''
	 */
	private $_appSecret = '';

	/**
	 * @var OAuthToken $_token
	 */
	private $_token;

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
	 * @param object $options
	 *
	 * @return mixed
	 */
	public function OAuthApplication ($appId, $appSecret, $options = null) {
		$this->_appId = $appId;
		$this->_appPublic = isset($options->AppPublic) ? $options->AppPublic : '';
		$this->_appSecret = $appSecret;
	}

	/**
	 * @param string $redirect
	 * @param string[] $scope
	 *
	 * @return string
	 */
	public function OAuthLoginURL ($redirect, $scope) {
		if (!$scope)
			$scope = array(self::SCOPE_VALUABLE_ACCESS);

		return QuarkURI::Build(self::URL_OAUTH, array(
			'client_id' => $this->_appId,
			'redirect_uri' => $redirect,
			'state' => Quark::GuID(),
			'scope' => implode(',', (array)$scope),
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

		$req = QuarkDTO::ForPOST(new QuarkFormIOProcessor());
		$req->URIParams(array(
			'client_id' => $this->_appId,
			'client_secret' => $this->_appSecret,
			'redirect_uri' => $redirect,
			'code' => $request->code,
			'grant_type' => OAuthConfig::GRANT_AUTHORIZATION_CODE
		));

		$res = new QuarkDTO(new QuarkJSONIOProcessor());

		$api = $this->OAuthAPI('/oauth/token.do', $req, $res);

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
	 * @param $item
	 * @param bool $photo = false
	 *
	 * @return SocialNetworkUser
	 */
	private static function _user ($item, $photo = false) {
		if (!$item) return null;

		$user = new SocialNetworkUser($item->uid, $item->name);

		$user->PhotoFromLink($item->pic_3, $photo);
		$user->Page(self::URL_PROFILE . $item->uid);
		$user->BirthdayByDate('Y-m-d', $item->birthday);
		$user->Location(''
			. (isset($item->location->country) ? $item->location->country  . ' ' : '')
			. (isset($item->location->countryName) ? '(' . $item->location->countryName  . ') ' : '')
			. (isset($item->location->countryCode) ? '(' . $item->location->countryCode  . ') ' : '')
			. (isset($item->location->city) ? $item->location->city  . ' ' : '')
		);

		if (isset($item->email)) $user->Email($item->email);
		if ($item->gender == self::GENDER_MALE) $user->Gender(SocialNetworkUser::GENDER_MALE);
		if ($item->gender == self::GENDER_FEMALE) $user->Gender(SocialNetworkUser::GENDER_FEMALE);

		return $user;
	}

	/**
	 * @return string
	 */
	private static function _fields () {
		return array(
			self::FIELD_UID,
			self::FIELD_NAME,
			self::FIELD_PIC_3,
			self::FIELD_BIRTHDAY,
			self::FIELD_LOCATION,
			self::FIELD_EMAIL,
			self::FIELD_GENDER
		);
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
		return $count;
	}

	/**
	 * @param string $user
	 *
	 * @return SocialNetworkUser
	 */
	public function SocialNetworkUser ($user) {
		return self::_user($this->OKAPI('users.getCurrentUser'));
	}

	/**
	 * @note requires application scope approving
	 *
	 * @param string $user
	 * @param int $count
	 * @param int $offset
	 *
	 * @return SocialNetworkUser[]
	 */
	public function SocialNetworkFriends ($user, $count, $offset) {
		$req = QuarkDTO::ForGET(new QuarkFormIOProcessor());
		$req->URIInit(array(
			'fid' => $user
		));

		$api = $this->OKAPI('friends.get', $req);

		if (!$api || !is_array($api->Data())) return array();

		$ids = array_slice($api->Data(), $count, $offset);

		$req = QuarkDTO::ForGET(new QuarkFormIOProcessor());
		$req->URIInit(array(
			'uids' => $ids,
			'fields' => self::_fields()
		));

		$api = $this->OKAPI('users.getInfo', $req);

		if (!$api) return array();

		$users = $api->Data();
		$friends = array();

		foreach ($users as $item)
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
	 * @param array $params = []
	 *
	 * @return string
	 */
	public function OKSign ($params = []) {
		unset($params->access_token);

		ksort($params);
		$out = '';

		foreach ($params as $key => $value)
			$out .= $key . '=' . $value;

		return md5($out . ($this->_token ? md5($this->_token->access_token . $this->_appSecret) : $this->_appSecret));
	}

	/**
	 * @param string $method = ''
	 * @param QuarkDTO $request = null
	 *
	 * @return QuarkDTO|null
	 *
	 * @throws OAuthAPIException
	 */
	public function OKAPI ($method = '', QuarkDTO $request = null) {
		if ($request == null) $request = QuarkDTO::ForGET();

		$params = QuarkObject::Merge($request->URI() ? $request->URI()->Params() : (object)array(), (object)array(
			'method' => $method,
			'application_key' => $this->_appPublic,
			'format' => 'json'
		));

		$params->sig = $this->OKSign((array)$params);

		$request->URIInit($params);

		return $this->OAuthAPI('/fb.do', $request, new QuarkDTO(new QuarkJSONIOProcessor()));
	}
}