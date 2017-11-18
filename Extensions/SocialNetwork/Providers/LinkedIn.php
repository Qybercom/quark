<?php
namespace Quark\Extensions\SocialNetwork\Providers;

use Quark\Quark;
use Quark\QuarkURI;
use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkKeyValuePair;
use Quark\QuarkModel;

use Quark\Extensions\OAuth\IQuarkOAuthConsumer;
use Quark\Extensions\OAuth\IQuarkOAuthProvider;
use Quark\Extensions\OAuth\OAuthAPIException;
use Quark\Extensions\OAuth\OAuthToken;
use Quark\Extensions\OAuth\OAuthConfig;

use Quark\Extensions\SocialNetwork\IQuarkSocialNetworkProvider;
use Quark\Extensions\SocialNetwork\SocialNetwork;
use Quark\Extensions\SocialNetwork\SocialNetworkUser;
use Quark\Extensions\SocialNetwork\SocialNetworkPost;

/**
 * Class LinkedIn
 *
 * @package Quark\Extensions\SocialNetwork\Providers
 */
class LinkedIn implements IQuarkOAuthProvider, IQuarkSocialNetworkProvider {
	const URL_OAUTH = 'https://www.linkedin.com/oauth/v2';
	const URL_API = 'https://api.linkedin.com';

	const PERMISSION_PROFILE_BASIC = 'r_basicprofile';
	const PERMISSION_PROFILE_FULL = 'r_fullprofile';
	const PERMISSION_EMAIL = 'r_emailaddress';
	const PERMISSION_SHARE = 'w_share';
	const PERMISSION_COMPANY_ADMIN = 'rw_company_admin';

	const HEADER_FORMAT = 'x-li-format';

	const CURRENT_USER = '~';

	const FIELD_ID = 'id';
	const FIELD_FIRST_NAME = 'first-name';
	const FIELD_LAST_NAME = 'last-name';
	const FIELD_MAIDEN_NAME = 'maiden-name';
	const FIELD_FORMATTED_NAME = 'formatted-name';
	const FIELD_PHONETIC_FIRST_NAME = 'phonetic-first-name';
	const FIELD_PHONETIC_LAST_NAME = 'phonetic-last-name';
	const FIELD_FORMATTED_PHONETIC_NAME = 'formatted-phonetic-name';
	const FIELD_HEADLINE = 'headline';
	const FIELD_LOCATION = 'location';
	const FIELD_INDUSTRY = 'industry';
	const FIELD_CURRENT_SHARE = 'current-share';
	const FIELD_NUM_CONNECTIONS = 'num-connections';
	const FIELD_NUM_CONNECTIONS_CAPPED = 'num-connections-capped';
	const FIELD_SUMMARY = 'summary';
	const FIELD_SPECIALTIES = 'specialties';
	const FIELD_POSITIONS = 'positions';
	const FIELD_PICTURE_URL = 'picture-url';
	const FIELD_PICTURE_URLS_ORIGINAL = 'picture-urls::(original)';
	const FIELD_SITE_STANDART_PROFILE_REQUEST = 'site-standard-profile-request';
	const FIELD_API_STANDART_PROFILE_REQUEST = 'api-standard-profile-request';
	const FIELD_PUBLIC_PROFILE_URL = 'public-profile-url';
	const FIELD_EMAIL = 'email-address';

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
		return QuarkURI::Build(self::URL_OAUTH . '/authorization', array(
			'client_id' => $this->_appId,
			'redirect_uri' => $redirect,
			'state' => Quark::GuID(),
			'scope' => implode(',', is_array($scope) && sizeof($scope) != 0 ? $scope : array(self::PERMISSION_PROFILE_BASIC)),
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

		$api = $this->OAuthAPI('/accessToken', $req, new QuarkDTO(new QuarkJSONIOProcessor()), self::URL_OAUTH);

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

		$request->Header(self::HEADER_FORMAT, 'json');

		if ($this->_token != null)
			$request->Authorization(new QuarkKeyValuePair('Bearer', $this->_token->access_token));

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
			self::FIELD_FIRST_NAME,
			self::FIELD_LAST_NAME,
			self::FIELD_HEADLINE,
			self::FIELD_LOCATION,
			self::FIELD_PUBLIC_PROFILE_URL,
			self::FIELD_PICTURE_URL,
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
		$user = new SocialNetworkUser($item->id, $item->firstName . ' ' . $item->lastName);

		$user->PhotoFromLink(isset($item->pictureUrl) ? $item->pictureUrl : '', $photo);
		$user->Bio($item->headline);
		$user->Page($item->publicProfileUrl);
		$user->Location($item->location->name);

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
		$response = $this->OAuthAPI(
			'/v1/people/'
			. $user
			. ':(' . self::_fields($fields) . ')'
			, $request
		);

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
	 *
	 * @return SocialNetworkPost
	 */
	public function SocialNetworkPublish (SocialNetworkPost $post) {
		// TODO: Implement SocialNetworkPublish() method.
	}
}