<?php
namespace Quark\Extensions\SocialNetwork\Providers;

use Quark\Quark;
use Quark\QuarkFormIOProcessor;
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
use Quark\Extensions\SocialNetwork\SocialNetworkPost;

/**
 * Class Facebook
 *
 * @package Quark\Extensions\SocialNetwork\Providers
 */
class Facebook implements IQuarkOAuthProvider, IQuarkSocialNetworkProvider {
	const URL_OAUTH_LOGIN = 'https://www.facebook.com/v2.10/dialog/oauth';
	const URL_OAUTH_LOGOUT = 'https://www.facebook.com/logout.php';
	const URL_API = 'https://graph.facebook.com/v2.10';

	const CURRENT_USER = 'me';

	const FIELD_ID = 'id';
	const FIELD_NAME = 'name';
	const FIELD_PICTURE = 'picture.width(1200).height(1200)';
	const FIELD_GENDER = 'gender';
	const FIELD_LINK = 'link';
	const FIELD_EMAIL = 'email';
	const FIELD_BIRTHDAY = 'birthday';
	const FIELD_ABOUT = 'about';

	const PERMISSION_PUBLIC_PROFILE = 'public_profile';
	const PERMISSION_EMAIL = 'email';
	const PERMISSION_USER_BIRTHDAY = 'user_birthday';
	const PERMISSION_USER_FRIENDS = 'user_friends';
	const PERMISSION_USER_LIKES = 'user_likes';
	const PERMISSION_PUBLISH_ACTIONS = 'publish_actions';
	const PERMISSION_PUBLISH_PAGES = 'publish_pages';

	const PUBLISH_AUDIENCE_EVERYONE = 'EVERYONE';
	const PUBLISH_AUDIENCE_FRIENDS_ALL = 'ALL_FRIENDS';
	const PUBLISH_AUDIENCE_FRIENDS_OF_FRIENDS = 'FRIENDS_OF_FRIENDS';
	const PUBLISH_AUDIENCE_SELF = 'SELF';
	const PUBLISH_AUDIENCE_CUSTOM = 'CUSTOM';

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
	 * @var array $_audience = []
	 */
	private $_audience = array('value' => self::PUBLISH_AUDIENCE_SELF);

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
	 * @param $options = null
	 *
	 * @return mixed
	 */
	public function OAuthApplication ($appId, $appSecret, $options = null) {
		$this->_appId = $appId;
		$this->_appSecret = $appSecret;

		if (isset($options->FacebookPublishAudience)) {
			$default = array(
				self::PUBLISH_AUDIENCE_EVERYONE,
				self::PUBLISH_AUDIENCE_FRIENDS_ALL,
				self::PUBLISH_AUDIENCE_FRIENDS_OF_FRIENDS,
				self::PUBLISH_AUDIENCE_SELF
			);

			$this->_audience = in_array($options->FacebookPublishAudience, $default)
				? array('value' => $options->FacebookPublishAudience)
				: array(
					'value' => self::PUBLISH_AUDIENCE_CUSTOM,
					'allow' => explode(',', $options->FacebookPublishAudience)
				);
		}
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
			self::FIELD_ID,
			self::FIELD_NAME,
			self::FIELD_LINK,
			self::FIELD_GENDER,
			self::FIELD_PICTURE,
			self::FIELD_BIRTHDAY,
			self::FIELD_EMAIL
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
		$user->Verified($item->verified);

		if (isset($item->about))
			$user->Bio($item->about);

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
		$request = QuarkDTO::ForGET(new QuarkJSONIOProcessor());
		$request->URIParams(array(
			'fields' => self::_fields($fields)
		));

		$response = $this->OAuthAPI('/' . $user, $request);

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

		$response = $this->OAuthAPI('/' . $user . '/friends', $request);

		if ($response == null || !isset($response->data) || !is_array($response->data)) return array();

		$friends = array();

		foreach ($response->data as $item)
			$friends[] = self::_user($item);

		return $friends;
	}

	/**
	 * @param SocialNetworkPost $post
	 *
	 * @return SocialNetworkPost
	 */
	public function SocialNetworkPublish (SocialNetworkPost $post) {
		$target = $post->Target();
		$audience = $post->Audience();

		$request = QuarkDTO::ForPOST(new QuarkFormIOProcessor());
		$request->Data(array(
			'message' => $post->Content(),
			'privacy' => $audience ? $audience : $this->_audience
		));

		$response = $this->OAuthAPI(
			'/' . $target . '/feed',
			$request,
			new QuarkDTO(new QuarkJSONIOProcessor())
		);

		if (!isset($response->id))
			return null;

		$post->ID($response->id);

		return $post;
	}
}