<?php
namespace Quark\Extensions\OAuth\Providers;

use Quark\Extensions\OAuth\OAuthUser;
use Quark\Quark;
use Quark\QuarkURI;
use Quark\QuarkDTO;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkKeyValuePair;
use Quark\QuarkModel;

use Quark\Extensions\OAuth\IQuarkOAuthConsumer;
use Quark\Extensions\OAuth\IQuarkOAuthProvider;
use Quark\Extensions\OAuth\OAuthConfig;
use Quark\Extensions\OAuth\OAuthClient;
use Quark\Extensions\OAuth\OAuthToken;
use Quark\Extensions\OAuth\OAuthAPIException;

/**
 * Class GoogleOAuth
 *
 * @package Quark\Extensions\OAuth\Providers
 */
class GoogleOAuth implements IQuarkOAuthProvider {
	const URL_OAUTH_LOGIN = 'https://accounts.google.com/o/oauth2/v2/auth';
	const URL_OAUTH_SCOPE = 'https://www.googleapis.com/auth/';
	const URL_API = 'https://www.googleapis.com';

	const ACCESS_ONLINE = 'online';
	const ACCESS_OFFLINE = 'offline';

	const SCOPE_USERINFO_PROFILE = 'userinfo.profile';
	const SCOPE_USERINFO_EMAIL = 'userinfo.email';
	const SCOPE_PLUS_LOGIN = 'plus.login';
	const SCOPE_PLUS_ME = 'plus.me';

	const PROMPT_CONSENT = 'consent';
	const PROMPT_SELECT_ACCOUNT = 'select_account';

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
	protected $_token;

	/**
	 * @var string[] $_defaultScope
	 */
	protected $_defaultScope = array(self::SCOPE_USERINFO_PROFILE);

	/**
	 * @param OAuthToken $token
	 *
	 * @return IQuarkOAuthConsumer
	 */
	public function OAuthConsumer (OAuthToken $token) {
		$this->_token = $token;

		return new OAuthClient();
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
		if ($scope == null)
			$scope = $this->_defaultScope;

		return QuarkURI::Build(self::URL_OAUTH_LOGIN, array(
			'client_id' => $this->_appId,
			'redirect_uri' => $redirect,
			'state' => Quark::GuID(),
			'scope' => implode(' ', array_map(function ($elem) { return self::URL_OAUTH_SCOPE . $elem; }, $scope)),
			'response_type' => OAuthConfig::RESPONSE_CODE,
			'access_type' => self::ACCESS_OFFLINE,
			//'include_granted_scopes' => 'true', // for incremental auth
			'prompt' => self::PROMPT_CONSENT
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

		$api = $this->OAuthAPI('/oauth2/v4/token', $req);

		return $api == null ? null : new QuarkModel(new OAuthToken(), $api->Data());
	}

	/**
	 * @param OAuthToken $token
	 *
	 * @return OAuthToken
	 */
	public function OAuthTokenRefresh (OAuthToken $token) {
		$req = QuarkDTO::ForPOST(new QuarkFormIOProcessor());
		$req->Data(array(
			'client_id' => $config->AppID(),
			'client_secret' => $config->AppSecret(),
			'grant_type' => 'refresh_token',
			'refresh_token' => $token->token_refresh
		));

		$res = $consumer->OAuthAPI('/token', $req, new QuarkDTO(new QuarkJSONIOProcessor()), 'https://oauth2.googleapis.com');

		if (!isset($res->access_token)) return null;

		$token->access_token = $res->access_token;
		$token->expires_in = $res->expires_in;

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

		if ($this->_token != null && isset($this->_token->access_token)) // TODO: verify flow, sometimes 'undefined property access_token'
			$request->Authorization(new QuarkKeyValuePair('Bearer', $this->_token->access_token));

		if ($base === null)
			$base = self::URL_API;

		$api = QuarkHTTPClient::To($base . $url, $request, $response);

		if (isset($api->error))
			throw new OAuthAPIException($request, $response);

		return $api;
	}

	/**
	 * @return OAuthUser
	 */
	public function OAuthUser () {
		$userInfo = $this->OAuthAPI('/oauth2/v3/userinfo');
		if ($userInfo == null) return null;

		$user = new OAuthUser($userInfo->id, $userInfo->name);
		$user->Email($userInfo->email);
		$user->AvatarFromLink($userInfo->picture);

		return $user;
	}
}