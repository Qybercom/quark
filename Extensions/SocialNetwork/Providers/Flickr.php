<?php
namespace Quark\Extensions\SocialNetwork\Providers;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkModel;

use Quark\Extensions\OAuth\IQuarkOAuthConsumer;
use Quark\Extensions\OAuth\IQuarkOAuthProvider;
use Quark\Extensions\OAuth\OAuthAPIException;
use Quark\Extensions\OAuth\OAuthToken;

use Quark\Extensions\SocialNetwork\IQuarkSocialNetworkProvider;
use Quark\Extensions\SocialNetwork\SocialNetwork;
use Quark\Extensions\SocialNetwork\SocialNetworkUser;
use Quark\Extensions\SocialNetwork\SocialNetworkPost;
use Quark\Extensions\SocialNetwork\SocialNetworkPublishingChannel;
use Quark\Extensions\SocialNetwork\SocialNetworkProviderBehavior;

/**
 * Class Flickr
 *
 * @package Quark\Extensions\SocialNetwork\Providers
 */
class Flickr implements IQuarkOAuthProvider, IQuarkSocialNetworkProvider {
	const URL_OAUTH = 'https://www.flickr.com/services/oauth';
	const URL_API = 'https://api.flickr.com/services/rest';

	use SocialNetworkProviderBehavior;

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
		$login = $this->OAuth1_0a_RequestToken($redirect, '/oauth/request_token', self::URL_OAUTH);

		Quark::Trace($login);

		return isset($login->oauth_token) ? self::URL_OAUTH . '/oauth/authorize?oauth_token=' . $login->oauth_token : null;
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
		// TODO: Implement OAuthTokenFromRequest() method.
	}

	/**
	 * @param OAuthToken $token
	 *
	 * @return OAuthToken
	 */
	public function OAuthTokenRefresh (OAuthToken $token) {
		return $token;
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
	 * @param array|object $data
	 *
	 * @return SocialNetworkUser
	 */
	public function SocialNetworkProfile ($data) {
		// TODO: Implement SocialNetworkProfile() method.
	}

	/**
	 * @param string $user
	 *
	 * @return string
	 */
	public function SocialNetworkParameterUser ($user) {
		// TODO: Implement SocialNetworkParameterUser() method.
	}

	/**
	 * @param int $count
	 *
	 * @return int
	 */
	public function SocialNetworkParameterFriendsCount ($count) {
		// TODO: Implement SocialNetworkParameterFriendsCount() method.
	}

	/**
	 * @param string $user
	 *
	 * @return SocialNetworkUser
	 */
	public function SocialNetworkUser ($user) {
		// TODO: Implement SocialNetworkUser() method.
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
	 * Limit of the post length
	 *
	 * @return int
	 */
	public function SocialNetworkPublishingLengthLimit () {
		return SocialNetwork::PUBLISHING_LIMIT_NONE;
	}
}