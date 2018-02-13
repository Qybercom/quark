<?php
namespace Quark\Extensions\SocialNetwork\Providers;

use Quark\QuarkDate;
use Quark\QuarkDTO;
use Quark\QuarkURI;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkKeyValuePair;
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
 * Class Wordpress
 *
 * @package Quark\Extensions\SocialNetwork\Providers
 */
class Wordpress implements IQuarkOAuthProvider, IQuarkSocialNetworkProvider {
	const URL_OAUTH = 'https://public-api.wordpress.com/oauth2';
	const URL_API = 'https://public-api.wordpress.com/rest/v1';

	const SCOPE_AUTH = 'auth';
	const SCOPE_GLOBAL = 'global';

	const CURRENT_USER = 'me';

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
		$auth = array(
			'client_id' => $this->_appId,
			'redirect_uri' => $redirect,
			'response_type' => OAuthConfig::RESPONSE_CODE
		);

		if ($scope)
			$auth['scope'] = implode(',', $scope);

		return QuarkURI::Build(self::URL_OAUTH . '/authorize', $auth);
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

		$api = $this->OAuthAPI('/token', $req, new QuarkDTO(new QuarkJSONIOProcessor()), self::URL_OAUTH);

		return $api == null ? null : new QuarkModel(new OAuthToken(), $api->Data());
	}

	/**
	 * @param string $url
	 * @param QuarkDTO $request
	 * @param QuarkDTO $response
	 * @param string $base = self::URL_API
	 *
	 * @return QuarkDTO|null
	 *
	 * @throws OAuthAPIException
	 */
	public function OAuthAPI ($url, QuarkDTO $request, QuarkDTO $response = null, $base = self::URL_API) {
		if ($request == null) $request = QuarkDTO::ForGET(new QuarkJSONIOProcessor());
		if ($response == null) $response = new QuarkDTO(new QuarkJSONIOProcessor());

		if ($this->_token != null)
			$request->Authorization(new QuarkKeyValuePair('Bearer', isset($this->_token->access_token) ? $this->_token->access_token : ''));

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
		$user = new SocialNetworkUser($item->ID, $item->display_name);

		$user->PhotoFromLink(isset($item->avatar_URL) ? $item->avatar_URL : '', $photo);
		$user->Bio($item->headline);
		$user->Page($item->profile_URL);
		$user->Email($item->email);
		$user->Username($item->username);
		$user->RegisteredAt(QuarkDate::GMTOf($item->date));
		$user->Verified($item->verified);

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
		$response = $this->OAuthAPI('/' . $user, $request);

		if ($response == null) return null;

		return self::_user($response);
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
	 * @param bool $primary = false
	 * @param bool $categoriesById = true
	 *
	 * @return SocialNetworkPost
	 */
	public function SocialNetworkPublish (SocialNetworkPost $post, $primary = false, $categoriesById = true) {
		$site = $post->Target();

		if ($primary && !$site) {
			$author = $post->Author();

			$request = QuarkDTO::ForGET(new QuarkJSONIOProcessor());
			$response = $this->OAuthAPI('/' . $this->SocialNetworkParameterUser($author), $request);

			if ($response == null) return null;

			$site = $response->primary_blog;
		}

		$request = QuarkDTO::ForPOST(new QuarkJSONIOProcessor());

		$data = array(
			'title' => $post->Title(),
			'content' => $post->Content(),
			//'status' => $post->Audience()
		);

		// TODO: for v1.2 categories and categories_by_id are split
		/*if ($categoriesById) $data['categories_by_id'] = $post->Categories();
		else */$data['categories'] = $post->Categories();

		$request->Data($data);

		$response = $this->OAuthAPI('/sites/' . $site . '/posts/new', $request);

		if (!isset($response->ID)) return null;

		$post->ID($response->ID);
		$post->URL($response->URL);
		$post->Author($response->author->name);

		$created = QuarkDate::GMTOf($response->date);
		$post->DateCreated(QuarkDate::FromTimestamp($created->Timestamp()));

		$updated = QuarkDate::GMTOf($response->modified);
		$post->DateUpdated(QuarkDate::FromTimestamp($updated->Timestamp()));

		return $post;
	}

	/**
	 * @param string $user
	 *
	 * @return SocialNetworkPublishingChannel[]
	 */
	public function SocialNetworkPublishingChannels ($user) {
		$requestSites = QuarkDTO::ForGET(new QuarkJSONIOProcessor());
		$responseSites = $this->OAuthAPI('/' . $user . '/sites', $requestSites);

		if (!isset($responseSites->sites) || !is_array($responseSites->sites)) return array();

		$out = array();

		foreach ($responseSites->sites as $site) {
			$requestCategories = QuarkDTO::ForGET(new QuarkJSONIOProcessor());
			$responseCategories = $this->OAuthAPI('/sites/' . $site->ID . '/categories', $requestCategories);

			if (isset($responseCategories->categories) && is_array($responseCategories->categories))
				foreach ($responseCategories->categories as $category) {
					$channel = new SocialNetworkPublishingChannel($site->ID . '-' . $category->ID, $site->name . ' - ' . $category->name);
					$channel->Description($category->description);

					$out[] = $channel;
				}
		}

		return $out;
	}
}