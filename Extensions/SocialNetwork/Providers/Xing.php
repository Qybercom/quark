<?php
namespace Quark\Extensions\SocialNetwork\Providers;

use Quark\IQuarkModel;
use Quark\IQuarkModelWithCustomCollectionName;
use Quark\IQuarkModelWithDataProvider;
use Quark\IQuarkStrongModel;

use Quark\Quark;
use Quark\QuarkDate;
use Quark\QuarkDTO;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkModel;
use Quark\QuarkModelBehavior;

use Quark\DataProviders\QuarkDNA;

use Quark\Extensions\OAuth\IQuarkOAuthConsumer;
use Quark\Extensions\OAuth\IQuarkOAuthProvider;
use Quark\Extensions\OAuth\OAuthToken;
use Quark\Extensions\OAuth\OAuthAPIException;
use Quark\Extensions\OAuth\OAuthProviderBehavior;

use Quark\Extensions\SocialNetwork\IQuarkSocialNetworkProvider;
use Quark\Extensions\SocialNetwork\SocialNetwork;
use Quark\Extensions\SocialNetwork\SocialNetworkUser;
use Quark\Extensions\SocialNetwork\SocialNetworkPost;
use Quark\Extensions\SocialNetwork\SocialNetworkPublishingChannel;

/**
 * Class Xing
 *
 * @property string $oauth_token
 * @property string $oauth_token_secret
 *
 * @package Quark\Extensions\SocialNetwork\Providers
 */
class Xing implements IQuarkOAuthProvider, IQuarkSocialNetworkProvider, IQuarkModel, IQuarkStrongModel, IQuarkModelWithDataProvider, IQuarkModelWithCustomCollectionName {
	const URL_API = 'https://api.xing.com';

	const CURRENT_USER = 'me';

	const FIELD_ID = 'id';
	const FIELD_ACTIVE_EMAIL = 'active_email';
	const FIELD_TIME_ZONE = 'time_zone';
	const FIELD_DISPLAY_NAME = 'display_name';
	const FIELD_FIRST_NAME = 'first_name';
	const FIELD_LAST_NAME = 'last_name';
	const FIELD_GENDER = 'gender';
	const FIELD_PAGE_NAME = 'page_name';
	const FIELD_BIRTH_DATE = 'birth_date';
	const FIELD_WANTS = 'wants';
	const FIELD_HAVES = 'haves';
	const FIELD_TOP_HAVES = 'top_haves';
	const FIELD_INTERESTS = 'interests';
	const FIELD_WEB_PROFILES = 'web_profiles';
	const FIELD_BADGES = 'badges';
	const FIELD_LEGAL_INFORMATION = 'legal_information';
	const FIELD_OCCUPATION_TITLE = 'occupation_title';
	const FIELD_OCCUPATION_ORG = 'occupation_org';
	const FIELD_PROFESSIONAL_EXPERIENCE = 'professional_experience';
	const FIELD_PHOTO_URLS = 'photo_urls';
	const FIELD_PHOTO_ATTRIBUTES = 'photo_attributes';
	const FIELD_PERMALINK = 'permalink';
	const FIELD_LANGUAGES = 'languages';
	const FIELD_EMPLOYMENT_STATUS = 'employment_status';
	const FIELD_ORGANISATION_MEMBER = 'organisation_member';
	const FIELD_INSTANT_MESSAGING_ACCOUNTS = 'instant_messaging_accounts';
	const FIELD_EDUCATIONAL_BACKGROUND = 'educational_background';

	const CONTACTS_LIMIT = 100;

	const STORAGE = 'quark.social.xing';
	const COLLECTION = 'SocialXing';

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
	 * @var bool $_signQuery = false
	 */
	private $_signQuery = false;

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
		$login = $this->OAuth1_0a_RequestToken($redirect, '/v1/request_token', self::URL_API);

		/**
		 * @var QuarkModel|Xing $token
		 */
		$token = new QuarkModel($this, $login->Data());

		if (!$token->Create()) {
			Quark::Log('[Social.Xing] Unable to create OAuth token record. Check that you correctly set data provider. Now used "' . $this->_storage . '" with collection "' . $this->_collection . '"');
			Quark::Trace($token->RawValidationErrors());

			return null;
		}

		return isset($login->oauth_token) ? self::URL_API . '/v1/authorize?oauth_token=' . $login->oauth_token : null;
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
		$this->_signQuery = true;

		/**
		 * @var QuarkModel|Xing $token
		 */
		$token = QuarkModel::FindOne($this, array(
			'oauth_token' => $request->oauth_token
		));

		if ($token == null || !$token->Remove()) {
			Quark::Log('[Social.Xing] Unable to get or remove selected OAuth token record for "' . $request->oauth_token . '". Check that you correctly set data provider. Now used "' . $this->_storage . '" with collection "' . $this->_collection . '"');

			return null;
		}

		$out = $this->OAuth1_0a_TokenFromRequest($request, '/v1/access_token', self::URL_API, $token->oauth_token_secret, QuarkDTO::ForGET(new QuarkFormIOProcessor()));

		$this->_signQuery = false;
		$this->_callback = null;

		return $out;
	}

	/**
	 * @param string $url = ''
	 * @param QuarkDTO $request = null
	 * @param QuarkDTO $response = null
	 * @param string $base = self::URL_API
	 * @param bool $dataSign = true
	 *
	 * @return QuarkDTO|null
	 *
	 * @throws OAuthAPIException
	 */
	public function OAuthAPI ($url = '', QuarkDTO $request = null, QuarkDTO $response = null, $base = self::URL_API, $dataSign = true) {
		if ($request == null) $request = QuarkDTO::ForGET(new QuarkFormIOProcessor());
		if ($response == null) $response = new QuarkDTO(new QuarkJSONIOProcessor());

		$data = $request->Data();

		if ($request->Method() == QuarkDTO::METHOD_GET) {
			if ($data)
				$request->URIParams($data);

			$data = $request->URIParams();
		}

		if ($this->_signQuery) $request->URIParams($this->OAuth1_0a_AuthorizationQuery($request->Method(), $base . $url, is_scalar($data) || !$dataSign ? array() : (array)$data));
		else $request->Authorization($this->OAuth1_0a_AuthorizationHeader($request->Method(), $base . $url, is_scalar($data) || !$dataSign ? array() : (array)$data));

		if ($request->Method() == QuarkDTO::METHOD_GET) {
			$query = $request->Processor()->Encode($data);
			if ($query) $url .= '?' . $query;

			$request->Data('');
		}

		$api = QuarkHTTPClient::To($base . $url, $request, $response);

		if (isset($api->errors) || isset($api->error) || isset($api->error_name))
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
			self::FIELD_ACTIVE_EMAIL,
			self::FIELD_TIME_ZONE,
			self::FIELD_DISPLAY_NAME,
			self::FIELD_FIRST_NAME,
			self::FIELD_LAST_NAME,
			self::FIELD_GENDER,
			self::FIELD_PAGE_NAME,
			self::FIELD_BIRTH_DATE,
			self::FIELD_WANTS,
			self::FIELD_HAVES,
			self::FIELD_TOP_HAVES,
			self::FIELD_INTERESTS,
			self::FIELD_WEB_PROFILES,
			self::FIELD_BADGES,
			self::FIELD_LEGAL_INFORMATION,
			//self::FIELD_OCCUPATION_TITLE,
			//self::FIELD_OCCUPATION_ORG,
			self::FIELD_PROFESSIONAL_EXPERIENCE,
			self::FIELD_PHOTO_URLS,
			self::FIELD_PHOTO_ATTRIBUTES,
			self::FIELD_PERMALINK,
			self::FIELD_LANGUAGES,
			self::FIELD_EMPLOYMENT_STATUS,
			self::FIELD_ORGANISATION_MEMBER,
			self::FIELD_INSTANT_MESSAGING_ACCOUNTS,
			self::FIELD_EDUCATIONAL_BACKGROUND
		));
	}

	/**
	 * @param $item
	 * @param bool $photo = false
	 *
	 * @return SocialNetworkUser
	 */
	private static function _user ($item, $photo = false) {
		$user = new SocialNetworkUser($item->id, $item->display_name, $item);

		$user->Email($item->active_email);
		$user->PhotoFromLink(isset($item->photo_urls->large) ? $item->photo_urls->large : '', $photo);
		$user->Gender($item->gender);
		$user->Page($item->permalink);
		$user->Company($item->professional_experience->primary_company->name);

		if ($item->birth_date->year && $item->birth_date->month && $item->birth_date->day)
			$user->Birthday(QuarkDate::GMTOf($item->birth_date->year . '-' . $item->birth_date->month . '-' . $item->birth_date->day));

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
		return $count;
	}

	/**
	 * @param string $user
	 * @param string[] $fields = []
	 *
	 * @return SocialNetworkUser
	 */
	public function SocialNetworkUser ($user, $fields = []) {
		$request = QuarkDTO::ForGET(new QuarkFormIOProcessor());

		$response = $this->OAuthAPI('/v1/users/' . ($user ? $user : self::CURRENT_USER), $request);

		if ($response == null || !isset($response->users) || !is_array($response->users) || sizeof($response->users) != 1) return null;

		return self::_user($response->users[0]);
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
		if ($count != SocialNetwork::FRIENDS_ALL)
			return $this->XingContacts($user, $count, $offset, $fields);

		$i = 0;
		$pages = $this->XingContactsPages($user, self::CONTACTS_LIMIT);
		$contacts = array();

		while ($i < $pages) {
			$contacts = array_merge($contacts, $this->XingContacts($user, self::CONTACTS_LIMIT, self::CONTACTS_LIMIT * $i));
			$i++;
		}

		return $contacts;
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

	/**
	 * @param string $user = self::CURRENT_USER
	 *
	 * @return int|null
	 *
	 * @throws OAuthAPIException
	 */
	public function XingContactsTotal ($user = self::CURRENT_USER) {
		$request = QuarkDTO::ForGET(new QuarkFormIOProcessor());
		$request->URIParams(array(
			'limit' => 0,
			'offset' => 0
		));

		$response = $this->OAuthAPI('/v1/users/' . ($user ? $user : self::CURRENT_USER) . '/contacts', $request);

		return isset($response->contacts->total) ? $response->contacts->total : null;
	}

	/**
	 * @param string $user = self::CURRENT_USER
	 * @param int $limit = self::CONTACTS_LIMIT
	 *
	 * @return int
	 */
	public function XingContactsPages ($user = self::CURRENT_USER, $limit = self::CONTACTS_LIMIT) {
		$total = $this->XingContactsTotal($user);

		return $total === null ? null : ceil($total / $limit);
	}

	/**
	 * @param string $user = self::CURRENT_USER
	 * @param int $count = self::CONTACTS_LIMIT
	 * @param int $offset = 0
	 * @param string[] $fields = []
	 *
	 * @return SocialNetworkUser[]
	 *
	 * @throws OAuthAPIException
	 */
	public function XingContacts ($user = self::CURRENT_USER, $count = self::CONTACTS_LIMIT, $offset = 0, $fields = []) {
		$request = QuarkDTO::ForGET(new QuarkFormIOProcessor());
		$request->URIParams(array(
			'user_fields' => self::_fields($fields),
			'limit' => $count,
			'offset' => $offset
		));

		$response = $this->OAuthAPI('/v1/users/' . ($user ? $user : self::CURRENT_USER) . '/contacts', $request);

		if (!isset($response->contacts->users) || !is_array($response->contacts->users)) return array();

		$friends = array();

		foreach ($response->contacts->users as $item)
			$friends[] = self::_user($item);

		return $friends;
	}

	/**
	 * @return string
	 */
	public function DataProvider () {
		if (!$this->_init) {
			QuarkDNA::RuntimeStorage($this->_storage, 'social.xing.qd');
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