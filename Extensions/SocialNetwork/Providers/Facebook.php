<?php
namespace Quark\Extensions\SocialNetwork\Providers;

use Quark\Quark;
use Quark\QuarkURI;
use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkModel;

use Quark\Extensions\OAuth\IQuarkOAuthProvider;
use Quark\Extensions\OAuth\IQuarkOAuthConsumer;
use Quark\Extensions\OAuth\OAuthToken;
use Quark\Extensions\OAuth\OAuthAPIException;

use Quark\Extensions\SocialNetwork\IQuarkSocialNetworkProvider;
use Quark\Extensions\SocialNetwork\SocialNetwork;
use Quark\Extensions\SocialNetwork\SocialNetworkUser;

/**
 * Class Facebook
 *
 * @package Quark\Extensions\SocialNetwork\Providers
 */
class Facebook implements IQuarkOAuthProvider, IQuarkSocialNetworkProvider {
	const URL_OAUTH_LOGIN = 'https://www.facebook.com/v2.9/dialog/oauth';
	const URL_OAUTH_LOGOUT = 'https://www.facebook.com/logout.php';
	const URL_API = 'https://graph.facebook.com/v2.9';

	const CURRENT_USER = 'me';

	const PERMISSION_ID = 'id';
	const PERMISSION_NAME = 'name';
	const PERMISSION_PICTURE = 'picture.width(1200).height(1200)';
	const PERMISSION_GENDER = 'gender';
	const PERMISSION_LINK = 'link';

	const PERMISSION_EMAIL = 'email';
	const PERMISSION_BIRTHDAY = 'birthday';
	const PERMISSION_FRIENDS = 'user_friends';

	const PERMISSION_LIKES = 'user_likes';
	const PERMISSION_PUBLISH_ACTIONS = 'publish_actions';

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
		return QuarkURI::Build(self::URL_OAUTH_LOGIN, array(
			'client_id' => $this->_appId,
			'redirect_uri' => $redirect,
			'state' => Quark::GuID(),
			'scope' => implode(',', (array)$scope)
		));
	}

	/**
	 * @param string $redirect
	 *
	 * @return string
	 */
	public function OAuthLogoutURL ($redirect) {
		return QuarkURI::Build(self::URL_OAUTH_LOGOUT . array(
			'next' => $redirect,
			'access_token' => $this->_token->access_token
		));
	}

	/**
	 * @param QuarkDTO $request
	 * @param string $redirect
	 *
	 * @return QuarkModel|OAuthToken
	 */
	public function OAuthTokenFromRequest (QuarkDTO $request, $redirect) {
		if (!isset($request->code)) return null;

		$req = QuarkDTO::ForGET(new QuarkJSONIOProcessor());
		$req->URIParams(array(
			'client_id' => $this->_appId,
			'client_secret' => $this->_appSecret,
			'redirect_uri' => $redirect,
			'code' => $request->code
		));

		$api = $this->OAuthAPI('/oauth/access_token', $req);

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
		if ($request == null) $request = QuarkDTO::ForGET(new QuarkJSONIOProcessor());
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
	private static function _fields ($fields) {
		return implode(',', $fields != null ? $fields : array(
			self::PERMISSION_ID,
			self::PERMISSION_NAME,
			self::PERMISSION_LINK,
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
		$user = new SocialNetworkUser($item->id, $item->name);

		$user->PhotoFromLink(isset($item->picture->data->url) ? $item->picture->data->url : '', $photo);
		$user->Gender($item->gender[0]);
		$user->Page($item->link);

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
	 * @param string[] $fields = []
	 *
	 * @return SocialNetworkUser
	 */
	public function SocialNetworkUser ($user, $fields = []) {
		$request = QuarkDTO::ForGET(new QuarkJSONIOProcessor());
		$request->URIParams(array(
			'fields' => self::_fields($fields)
		));

		$response = $this->OAuthAPI('/' . ($user ? $user : self::CURRENT_USER), $request);

		if ($response == null) return null;

		return self::_user($response);
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
		$request = QuarkDTO::ForGET(new QuarkJSONIOProcessor());
		$request->URIParams(array(
			'fields' => self::_fields($fields)
		));

		$response = $this->OAuthAPI('/' . ($user ? $user : self::CURRENT_USER) . '/friends', $request);

		if ($response == null || !isset($response->data) || !is_array($response->data)) return array();

		$friends = array();

		foreach ($response->data as $item)
			$friends[] = self::_user($item);

		return $friends;
	}
}