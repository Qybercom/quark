<?php
namespace Quark\Extensions\SocialNetwork\Providers;

use Quark\Extensions\SocialNetwork\SocialNetworkPostAttachment;
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
use Quark\Extensions\OAuth\OAuthError;

use Quark\Extensions\SocialNetwork\IQuarkSocialNetworkProvider;
use Quark\Extensions\SocialNetwork\SocialNetwork;
use Quark\Extensions\SocialNetwork\SocialNetworkUser;
use Quark\Extensions\SocialNetwork\SocialNetworkPost;
use Quark\Extensions\SocialNetwork\SocialNetworkPublishingChannel;

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
	const FIELD_SHORT_NAME = 'short_name';

	const PERMISSION_PUBLIC_PROFILE = 'public_profile';
	const PERMISSION_EMAIL = 'email';
	const PERMISSION_USER_BIRTHDAY = 'user_birthday';
	const PERMISSION_USER_FRIENDS = 'user_friends';
	const PERMISSION_USER_LIKES = 'user_likes';
	const PERMISSION_PUBLISH_ACTIONS = 'publish_actions';
	const PERMISSION_PUBLISH_PAGES = 'publish_pages';
	const PERMISSION_MANAGE_PAGES = 'manage_pages';
	const PERMISSION_READ_PAGE_MAILBOXES = 'read_page_mailboxes';
	const PERMISSION_PAGES_SHOW_LIST = 'pages_show_list';
	const PERMISSION_PAGES_MANAGE_CTA = 'pages_manage_cta';
	const PERMISSION_PAGES_MANAGE_INSTANT_ARTICLES = 'pages_manage_instant_articles';

	const PUBLISH_AUDIENCE_EVERYONE = 'EVERYONE';
	const PUBLISH_AUDIENCE_FRIENDS_ALL = 'ALL_FRIENDS';
	const PUBLISH_AUDIENCE_FRIENDS_OF_FRIENDS = 'FRIENDS_OF_FRIENDS';
	const PUBLISH_AUDIENCE_SELF = 'SELF';
	const PUBLISH_AUDIENCE_CUSTOM = 'CUSTOM';

	const PAGE_ROLE_ADMINISTER = 'ADMINISTER';
	const PAGE_ROLE_EDIT_PROFILE = 'EDIT_PROFILE';
	const PAGE_ROLE_CREATE_CONTENT = 'CREATE_CONTENT';
	const PAGE_ROLE_MODERATE_CONTENT = 'MODERATE_CONTENT';
	const PAGE_ROLE_CREATE_ADS = 'CREATE_ADS';
	const PAGE_ROLE_BASIC_ADMIN = 'BASIC_ADMIN';

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

		return isset($api->access_token) ? new QuarkModel(new OAuthToken(), $api->Data()) : null;
	}

	/**
	 * @param string $url = ''
	 * @param QuarkDTO $request = null
	 * @param QuarkDTO $response = null
	 * @param string $base = self::URL_API
	 * @param string $token = ''
	 *
	 * @return QuarkDTO|null
	 *
	 * @throws OAuthAPIException
	 */
	public function OAuthAPI ($url = '', QuarkDTO $request = null, QuarkDTO $response = null, $base = self::URL_API, $token = '') {
		if ($request == null) $request = QuarkDTO::ForGET(new QuarkJSONIOProcessor());
		if ($response == null) $response = new QuarkDTO(new QuarkJSONIOProcessor());

		if ($this->_token != null)
			$request->URIInit(array('access_token' => $this->_token->access_token));

		if ($token != '')
			$request->URIInit(array('access_token' => $token));

		$api = QuarkHTTPClient::To($base . $url, $request, $response);

		if (isset($api->error))
			throw new OAuthAPIException($request, $response, new OAuthError($api->error->type, $api->error->message));

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
			self::FIELD_EMAIL,
			self::FIELD_SHORT_NAME
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

		if (isset($item->id))
			$user->Username($item->id);

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
	 * @param bool $preview
	 *
	 * @return SocialNetworkPost
	 */
	public function SocialNetworkPublish (SocialNetworkPost $post, $preview) {
		// TODO: post from the voice of Page (need obtaining the Page access token)

		$author = $post->Author();
		$target = $post->Target();
		$audience = $post->Audience();

		$access_token = $post->AuthorPublic() != '' ? $this->FacebookPageAccessToken($target) : '';

		$data = array(
			'message' => $post->Content()
		);

		if ($target == self::CURRENT_USER || $author == $target)
			$data['privacy'] = /*$audience ? $audience : */$this->_audience; // TODO: handle audience

		$urls = array();
		$media = array();

		$attachments = $post->Attachments();

		foreach ($attachments as $i => &$attachment) {
			if ($attachment->Type() == SocialNetworkPostAttachment::TYPE_URL)
				$urls[] = $attachment->Content();

			if ($attachment->Type() == SocialNetworkPostAttachment::TYPE_IMAGE) {
				$id = $this->FacebookPhotoUpload($attachment, '', $target, $access_token);

				if ($id) $media[] = $id;
			}
		}

		if (sizeof($urls) != 0)
			$data['link'] = $urls[0];

		if (sizeof($media) != 0) {
			foreach ($media as $i => &$image)
				$data['attached_media[' . $i . ']'] = json_encode(array('media_fbid' => $image));
		}

		$request = QuarkDTO::ForPOST(new QuarkFormIOProcessor());
		$request->Data($data);

		if (!$preview) {
			$response = $this->OAuthAPI(
				'/' . $target . '/feed',
				$request,
				new QuarkDTO(new QuarkJSONIOProcessor()),
				self::URL_API,
				$access_token
			);

			if (!isset($response->id))
				return null;

			$post->ID($response->id);
		}

		return $post;
	}

	/**
	 * @param string $user
	 *
	 * @return SocialNetworkPublishingChannel[]
	 */
	public function SocialNetworkPublishingChannels ($user) {
		$profile = $this->SocialNetworkUser($user);
		if ($profile == null) return array();

		$channel = new SocialNetworkPublishingChannel($profile->ID(), $profile->Name(), $profile->Page());
		$channel->Description($profile->Bio());
		$channel->Logo($profile->PhotoLink());

		$out = array(
			$channel
		);

		$request = QuarkDTO::ForGET(new QuarkJSONIOProcessor());
		$response = $this->OAuthAPI(
			'/' . $user . '/accounts',
			$request,
			new QuarkDTO(new QuarkJSONIOProcessor())
		);

		if (isset($response->data) && is_array($response->data))	{
			foreach ($response->data as $item) {
				$channel = new SocialNetworkPublishingChannel($item->id, $item->name);
				$channel->Description($item->category);

				$out[] = $channel;
			}
		}

		return $out;
	}

	/**
	 * Limit of the post length
	 *
	 * @return int
	 */
	public function SocialNetworkPublishingLengthLimit () {
		return SocialNetwork::PUBLISHING_LIMIT_NONE;
	}

	/**
	 * @param SocialNetworkPostAttachment $attachment = null
	 * @param string $caption = ''
	 * @param string $user = self::CURRENT_USER
	 * @param string $token = ''
	 *
	 * @return string|null
	 * @throws OAuthAPIException
	 */
	public function FacebookPhotoUpload (SocialNetworkPostAttachment $attachment = null, $caption = '', $user = self::CURRENT_USER, $token = '') {
		if ($attachment == null | $attachment->Type() != SocialNetworkPostAttachment::TYPE_IMAGE) return null;

		$data = array(
			'url' => $attachment->Content(),
			'published' => 'false'
		);

		if ($caption != '')
			$data['caption'] = $caption;

		$request = QuarkDTO::ForPOST(new QuarkFormIOProcessor());
		$request->Data($data);

		$response = $this->OAuthAPI('/' . $user . '/photos', $request, new QuarkDTO(new QuarkJSONIOProcessor()), self::URL_API, $token);

		return isset($response->id) ? $response->id : null;
	}

	/**
	 * @param string $page = ''
	 *
	 * @return string
	 *
	 * @throws OAuthAPIException
	 */
	public function FacebookPageAccessToken ($page = '') {
		$request = QuarkDTO::ForGET(new QuarkFormIOProcessor());
		$request->URIParams(array(
			'fields' => 'access_token'
		));

		$response = $this->OAuthAPI('/' . $page, $request, new QuarkDTO(new QuarkJSONIOProcessor()));

		return isset($response->access_token) ? $response->access_token : '';
	}
}