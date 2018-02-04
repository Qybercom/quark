<?php
namespace Quark\Extensions\SocialNetwork\Providers;

use Quark\QuarkObject;
use Quark\QuarkURI;
use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkModel;

use Quark\Extensions\OAuth\IQuarkOAuthConsumer;
use Quark\Extensions\OAuth\IQuarkOAuthProvider;
use Quark\Extensions\OAuth\OAuthConfig;
use Quark\Extensions\OAuth\OAuthToken;
use Quark\Extensions\OAuth\OAuthAPIException;

use Quark\Extensions\SocialNetwork\IQuarkSocialNetworkProvider;
use Quark\Extensions\SocialNetwork\SocialNetwork;
use Quark\Extensions\SocialNetwork\SocialNetworkUser;
use Quark\Extensions\SocialNetwork\SocialNetworkPost;
use Quark\Extensions\SocialNetwork\SocialNetworkPublishingChannel;

/**
 * Class MyMailRU
 *
 * @package Quark\Extensions\SocialNetwork\Providers
 */
class MyMailRU implements IQuarkOAuthProvider, IQuarkSocialNetworkProvider {
	const URL_OAUTH = 'https://connect.mail.ru';
	const URL_API = 'http://www.appsmail.ru/platform/api';

	const GENDER_MALE = '0';
	const GENDER_FEMALE = '1';

	/**
	 * @var string $_appId
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
	 * @var string $_vid = ''
	 */
	private $_vid = '';

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
		return QuarkURI::Build(self::URL_OAUTH . '/oauth/authorize?', array(
			'client_id' => $this->_appId,
   			'redirect_uri' => $redirect,
   			'response_type' => 'code',
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
		$req->Data(array(
			'client_id' => $this->_appId,
			'client_secret' => $this->_appSecret,
			'redirect_uri' => $redirect,
			'code' => $request->code,
			'grant_type' => OAuthConfig::GRANT_AUTHORIZATION_CODE
		));

		$api = $this->OAuthAPI('/oauth/token', $req, null, self::URL_OAUTH);

		if ($api == null) return null;

		$this->_vid = isset($api->x_mailru_vid) ? $api->x_mailru_vid : '';

		return new QuarkModel(new OAuthToken(), $api->Data());
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
			$request->URIInit(array('session_key' => $this->_token->access_token));

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

		$user = new SocialNetworkUser($item->uid, $item->first_name . ' ' . $item->last_name);

		$user->Username($item->nick);
		$user->PhotoFromLink($item->pic, $photo);
		$user->Page($item->link);
		$user->Location(''
			. (isset($item->location->country->name) ? $item->location->country->name  . ' ' : '')
			. (isset($item->location->city->name) ? $item->location->city->name  . ' ' : '')
			. (isset($item->location->region->name) ? $item->location->region->name  . ' ' : '')
		);

		if (isset($item->birthday)) $user->BirthdayByDate('d.m.Y', $item->birthday);
		if (isset($item->email)) $user->Email($item->email);
		if ($item->sex == self::GENDER_MALE) $user->Gender(SocialNetworkUser::GENDER_MALE);
		if ($item->sex == self::GENDER_FEMALE) $user->Gender(SocialNetworkUser::GENDER_FEMALE);

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
		return $count;
	}

	/**
	 * @param string $user
	 *
	 * @return SocialNetworkUser
	 */
	public function SocialNetworkUser ($user) {
		$api = $this->MailRUAPI('users.getInfo');
		$users = $api->Data();

		return self::_user(isset($users[0]) ? $users[0] : null);
	}

	/**
	 * @param string $user
	 * @param int $count
	 * @param int $offset
	 *
	 * @return SocialNetworkUser[]
	 */
	public function SocialNetworkFriends ($user, $count, $offset) {
		$req = QuarkDTO::ForGET(new QuarkFormIOProcessor());
		$req->URIInit(array(
			'ext' => 1
		));

		$api = $this->MailRUAPI('friends.get', $req);

		if (!$api) return array();

		$users = $api->Data();
		$friends = array();

		foreach ($users as $item)
			$friends[] = self::_user($item);

		return $friends;
	}

	/**
	 * @param SocialNetworkPost $post
	 *
	 * @return SocialNetworkPost
	 */
	public function SocialNetworkPublish (SocialNetworkPost $post) {
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
	 * @param string $user = ''
	 * @param array $params = []
	 *
	 * @return string
	 */
	public function MailRUSign ($user = '', $params = []) {
		ksort($params);
		$out = '';

		foreach ($params as $key => $value)
			$out .= $key . '=' . $value;

		return md5($user . $out . $this->_appSecret);
	}

	/**
	 * @param string $method = ''
	 * @param QuarkDTO $request = null
	 * @param string $user = ''
	 *
	 * @return QuarkDTO|null
	 *
	 * @throws OAuthAPIException
	 */
	public function MailRUAPI ($method = '', QuarkDTO $request = null, $user = '') {
		if ($request == null) $request = QuarkDTO::ForGET();

		$params = QuarkObject::Merge($request->URI() ? $request->URI()->Params() : (object)array(), (object)array(
			'method' => $method,
			'app_id' => $this->_appId,
			'secure' => 1
		));

		if ($this->_token) $params->session_key = $this->_token->access_token;
		else $params->uid = func_num_args() == 3 ? $user : $this->_vid;

		$params->sig = $this->MailRUSign(func_num_args() == 3 ? $user : '', (array)$params);

		$request->URIInit($params);

		return $this->OAuthAPI('', $request, new QuarkDTO(new QuarkJSONIOProcessor()));
	}
}