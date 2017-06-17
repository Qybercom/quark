<?php
namespace Quark\Extensions\SocialNetwork\Providers;

use Quark\Quark;
use Quark\QuarkDate;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkURI;
use Quark\QuarkDTO;
use Quark\QuarkModel;

use Quark\Extensions\OAuth\IQuarkOAuthConsumer;
use Quark\Extensions\OAuth\IQuarkOAuthProvider;
use Quark\Extensions\OAuth\OAuthAPIException;
use Quark\Extensions\OAuth\OAuthToken;

use Quark\Extensions\SocialNetwork\IQuarkSocialNetworkProvider;
use Quark\Extensions\SocialNetwork\SocialNetwork;
use Quark\Extensions\SocialNetwork\SocialNetworkUser;

/**
 * Class GitHub
 *
 * @package Quark\Extensions\SocialNetwork\Providers
 */
class GitHub implements IQuarkOAuthProvider, IQuarkSocialNetworkProvider {
	const URL_BASE = 'https://github.com/';
	const URL_OAUTH = 'https://github.com/login/oauth';
	const URL_API = 'https://api.github.com';

	const CURRENT_USER = '';

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
		return QuarkURI::Build(self::URL_OAUTH . '/authorize', array(
			'client_id' => $this->_appId,
			'redirect_uri' => $redirect,
			'scope' => implode(' ', (array)$scope)
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

		$request->UserAgentQuark();

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

		$user = new SocialNetworkUser($item->id);

		$user->Username($item->login);
		$user->PhotoFromLink($item->avatar_url, $photo);
		$user->Page($item->html_url);

		if (isset($item->name)) $user->Name($item->name);
		if (isset($item->email)) $user->Email($item->email);
		if (isset($item->created_at)) $user->RegisteredAt(QuarkDate::GMTOf($item->created_at));
		if (isset($item->location)) $user->Location($item->location);
		if (isset($item->bio)) $user->Bio($item->bio);

		return $user;
	}

	/**
	 * @param string $user
	 *
	 * @return SocialNetworkUser
	 */
	public function SocialNetworkUser ($user) {
		$request = QuarkDTO::ForGET(new QuarkFormIOProcessor());

		$response = $this->OAuthAPI('/user' . ($user ? 's/' . $user : self::CURRENT_USER), $request);

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
		$request = QuarkDTO::ForGET(new QuarkFormIOProcessor());

		$response = $this->OAuthAPI('/users/' . $user . '/followers', $request);

		if ($response == null || !is_array($response->Data())) return array();

		$friends = array();
		$followers = $response->Data();

		foreach ($followers as $item)
			$friends[] = self::_user($item);

		return $friends;
	}
}