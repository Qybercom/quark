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
use Quark\Extensions\OAuth\OAuthError;

use Quark\Extensions\SocialNetwork\IQuarkSocialNetworkProvider;
use Quark\Extensions\SocialNetwork\SocialNetwork;
use Quark\Extensions\SocialNetwork\SocialNetworkUser;
use Quark\Extensions\SocialNetwork\SocialNetworkPost;
use Quark\Extensions\SocialNetwork\SocialNetworkPublishingChannel;

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
	const PERMISSION_NETWORK = 'r_network';
	const PERMISSION_COMPLIANCE = 'r_compliance';

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
	const FIELD_SITE_STANDARD_PROFILE_REQUEST = 'site-standard-profile-request';
	const FIELD_API_STANDARD_PROFILE_REQUEST = 'api-standard-profile-request';
	const FIELD_PUBLIC_PROFILE_URL = 'public-profile-url';
	const FIELD_EMAIL = 'email-address';

	const VISIBILITY_ANYONE = 'anyone';
	const VISIBILITY_CONNECTIONS_ONLY = 'connections-only';

	const MAX_COMPANIES = 100;

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
	 * @var string $_audience = self::VISIBILITY_ANYONE
	 */
	private $_audience = self::VISIBILITY_ANYONE;

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

		if (isset($options->LinkedInPublishAudience)) {
			$default = array(
				self::VISIBILITY_ANYONE,
				self::VISIBILITY_CONNECTIONS_ONLY
			);

			if (in_array($options->LinkedInPublishAudience, $default))
				$this->_audience = $options->LinkedInPublishAudience;
		}
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

		$eCode = isset($api->errorCode);
		$eServiceCode = isset($api->serviceErrorCode);

		if (isset($api->error) || $eCode || $eServiceCode) {
			$exception = null;

			if ($eCode) $exception = new OAuthError($api->errorCode, $api->message);
			if ($eServiceCode) $exception = new OAuthError($api->serviceErrorCode, $api->message);

			throw new OAuthAPIException($request, $response, $exception);
		}

		return $api;
	}

	/**
	 * @param string[] $fields = null
	 *
	 * @return string
	 */
	private static function _fields ($fields = null) {
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
		// https://developer.linkedin.com/docs/guide/v2/people/connections-api
		// https://api.linkedin.com/v2/connections?q=viewer&start=0&count=0&projection=(elements*(to~(id,localizedFirstName,localizedLastName)))
		$request = QuarkDTO::ForGET(new QuarkJSONIOProcessor());
		$request->URIParams(array(
			'q' => 'viewer',
			'start' => $offset,
			'count' => $count,
			'projection' => '(elements*(to~(' . self::_fields() . ')))'
		));
		$response = $this->OAuthAPI('/v2/connections', $request);

		if (!isset($response->elements) || !is_array($response->elements)) return array();

		$friends = array();

		foreach ($response->elements as $item)
			$friends[] = self::_user($item);

		return $friends;
	}

	/**
	 * @param SocialNetworkPost $post
	 *
	 * @return SocialNetworkPost
	 */
	public function SocialNetworkPublish (SocialNetworkPost $post) {
		$author = $post->Author();
		$target = $post->Target();
		$audience = $post->Audience();

		$request = QuarkDTO::ForPOST(new QuarkJSONIOProcessor());
		$request->Data(array(
			'comment' => $post->Content(),
			/*'content' => array(
				'title' => $post->Title(),
				'description' => $post->Content(),
				'submitted-url' => 'https://developer.linkedin.com',
				'submitted-image-url' => 'https://example.com/logo.png'
			),*/
			'visibility' => array(
				'code' => $audience ? $audience : $this->_audience
			)
		));
		$response = $this->OAuthAPI(
			$author == $target
				? '/v1/people/~/shares?format=json'
				: '/v1/companies/' . $target . '/shares?format=json',
			$request
		);

		if (!isset($response->updateUrl)) return null;

		$post->URL($response->updateUrl);

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

		$all = $this->_companies(0, 0, true);

		if (isset($all->_start) && isset($all->_total)) {
			$page = 0;
			$pages = ceil($all->_total / self::MAX_COMPANIES);

			while ($page < $pages) {
				$response = $this->_companies($page * self::MAX_COMPANIES, self::MAX_COMPANIES, true);

				if (isset($response->values) && is_array($response->values)) {
					foreach ($response->values as $value) {
						$channel = new SocialNetworkPublishingChannel($value->id, $value->name);

						$out[] = $channel;
					}
				}

				$page++;
			}
		}

		return $out;
	}

	/**
	 * @param int $start = 0
	 * @param int $count = self::MAX_COMPANIES
	 * @param bool $admin = true
	 *
	 * @return QuarkDTO
	 */
	private function _companies ($start = 0, $count = self::MAX_COMPANIES, $admin = true) {
		$request = QuarkDTO::ForGET(new QuarkJSONIOProcessor());
		$request->URIParams(array(
			'format' => 'json',
			'start' => $start,
			'count' => $count,
			'is-company-admin' => $admin ? 'true' : 'false'
		));

		return $this->OAuthAPI('/v1/companies', $request);
	}
}