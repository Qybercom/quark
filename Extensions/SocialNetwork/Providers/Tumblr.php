<?php
namespace Quark\Extensions\SocialNetwork\Providers;

use Quark\IQuarkModel;
use Quark\IQuarkModelWithCustomCollectionName;
use Quark\IQuarkModelWithDataProvider;
use Quark\IQuarkStrongModel;

use Quark\Quark;
use Quark\QuarkURI;
use Quark\QuarkDTO;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkModel;
use Quark\QuarkModelBehavior;

use Quark\DataProviders\QuarkDNA;

use Quark\Extensions\OAuth\IQuarkOAuthConsumer;
use Quark\Extensions\OAuth\IQuarkOAuthProvider;
use Quark\Extensions\OAuth\OAuthAPIException;
use Quark\Extensions\OAuth\OAuthToken;
use Quark\Extensions\OAuth\OAuthProviderBehavior;

use Quark\Extensions\SocialNetwork\IQuarkSocialNetworkProvider;
use Quark\Extensions\SocialNetwork\SocialNetwork;
use Quark\Extensions\SocialNetwork\SocialNetworkUser;
use Quark\Extensions\SocialNetwork\SocialNetworkPost;
use Quark\Extensions\SocialNetwork\SocialNetworkPublishingChannel;

/**
 * Class Tumblr
 *
 * @property string $oauth_token
 * @property string $oauth_token_secret
 *
 * @package Quark\Extensions\SocialNetwork\Providers
 */
class Tumblr implements IQuarkOAuthProvider, IQuarkSocialNetworkProvider, IQuarkModel, IQuarkStrongModel, IQuarkModelWithDataProvider, IQuarkModelWithCustomCollectionName {
	const URL_OAUTH = 'https://www.tumblr.com/oauth';
	const URL_API = 'https://api.tumblr.com/v2';
	const URL_BLOGS = '.tumblr.com';

	const AVATAR_48 = 48;

	const STORAGE = 'quark.social.tumblr';
	const COLLECTION = 'SocialTumblr';

	use OAuthProviderBehavior;
	use QuarkModelBehavior;

	/**
	 * @var string $_storage = self::STORAGE
	 */
	private $_storage = self::STORAGE;

	/**
	 * @var string $_collection = self::COLLECTION
	 */
	private $_collection = self::COLLECTION;

	/**
	 * @var bool $_init = false
	 */
	private $_init = false;

	/**
	 * @param string $storage = self::STORAGE
	 * @param string $collection = self::COLLECTION
	 */
	public function __construct ($storage = self::STORAGE, $collection = self::COLLECTION) {
		$this->_storage = $storage;
		$this->_collection = $collection;
		$this->_init = func_num_args() != 0;
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
		$login = $this->OAuth1_0a_RequestToken($redirect, '/request_token', self::URL_OAUTH);

		/**
		 * @var QuarkModel|Tumblr $token
		 */
		$token = new QuarkModel($this, $login->Data());

		if (!$token->Create()) {
			Quark::Log('[Social.Tumblr] Unable to create OAuth token record. Check that you correctly set data provider. Now used "' . $this->_storage . '" with collection "' . $this->_collection . '"');
			Quark::Trace($token->RawValidationErrors());

			return null;
		}

		return isset($login->oauth_token) ? self::URL_OAUTH . '/authorize?oauth_token=' . $login->oauth_token : null;
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
		$this->_callback = $redirect;
		$this->_verifier = $request->oauth_verifier;

		/**
		 * @var QuarkModel|Tumblr $token
		 */
		$token = QuarkModel::FindOne($this, array(
			'oauth_token' => $request->oauth_token
		));

		if ($token == null || !$token->Remove()) {
			Quark::Log('[Social.Tumblr] Unable to get or remove selected OAuth token record for "' . $request->oauth_token . '". Check that you correctly set data provider. Now used "' . $this->_storage . '" with collection "' . $this->_collection . '"');

			return null;
		}

		return $this->OAuth1_0a_TokenFromRequest($request, '/access_token', self::URL_OAUTH, $token->oauth_token_secret);
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
	private function _user ($item, $photo = false) {
		if ($item == null) return null;

		$user = new SocialNetworkUser($item->name, $item->title);

		$user->Page($item->url);
		$user->Username($item->name);
		$user->Name($item->title);
		$user->Bio($item->description);

		$avatar = $this->OAuthAPI('/blog/' . QuarkURI::FromURI($item->url)->host . '/avatar');
		$user->PhotoFromLink($avatar->response->avatar_url, $photo);

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
		$out = null;

		if ($user == SocialNetwork::CURRENT_USER) {
			$response = $this->OAuthAPI('/user/info', QuarkDTO::ForGET(new QuarkFormIOProcessor()), new QuarkDTO(new QuarkJSONIOProcessor()));
			$out = isset($response->response->user->blogs) && sizeof($response->response->user->blogs) != 0 ? $response->response->user->blogs[0] : null;
		}
		else {
			$response = $this->OAuthAPI('/blog/' . $user . '/info', QuarkDTO::ForGET(new QuarkFormIOProcessor()), new QuarkDTO(new QuarkJSONIOProcessor()));
			$out = isset($response->response->blog) ? $response->response->blog : null;
		}

		return $this->_user($out);
	}

	/**
	 * @param string $user
	 * @param int $count
	 * @param int $offset
	 *
	 * @return SocialNetworkUser[]
	 */
	public function SocialNetworkFriends ($user, $count, $offset) {
		// TODO: Implement SocialNetworkFriends() method.
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
	 * @return string
	 */
	public function DataProvider () {
		if (!$this->_init) {
			QuarkDNA::RuntimeStorage($this->_storage, 'social.tumblr.qd');
			$this->_init = true;
		}

		return $this->_storage;
	}

	/**
	 * @return string
	 */
	public function CollectionName () {
		return $this->_collection;
	}

	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			$this->DataProviderPk(),
			'oauth_token' => '',
			'oauth_token_secret' => ''
		);
	}

	/**
	 * @return mixed
	 */
	public function Rules () {
		// TODO: Implement Rules() method.
	}
}