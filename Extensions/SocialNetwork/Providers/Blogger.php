<?php
namespace Quark\Extensions\SocialNetwork\Providers;

use Quark\QuarkDTO;
use Quark\QuarkFormIOProcessor;

use Quark\Extensions\OAuth\IQuarkOAuthConsumer;
use Quark\Extensions\OAuth\IQuarkOAuthProvider;
use Quark\Extensions\OAuth\OAuthToken;
use Quark\Extensions\OAuth\Providers\GoogleOAuth;

use Quark\Extensions\SocialNetwork\IQuarkSocialNetworkProvider;
use Quark\Extensions\SocialNetwork\SocialNetwork;
use Quark\Extensions\SocialNetwork\SocialNetworkUser;
use Quark\Extensions\SocialNetwork\SocialNetworkPost;

/**
 * Class Blogger
 *
 * @package Quark\Extensions\SocialNetwork\Providers
 */
class Blogger extends GoogleOAuth implements IQuarkOAuthProvider, IQuarkSocialNetworkProvider {
	const URL_API_PEOPLE = 'https://people.googleapis.com/v1/';

	const SCOPE_CONTACTS = 'contacts';
	const SCOPE_CONTACTS_READONLY = 'contacts.readonly';

	const GENDER_MALE = 'male';
	const GENDER_FEMALE = 'female';

	const CURRENT_USER = 'me';

	const AGGREGATE_COUNT = 100;
	const AGGREGATE_CURSOR = '';

	/**
	 * @var string[] $_defaultScope
	 */
	protected $_defaultScope = array(
		self::SCOPE_PLUS_LOGIN
	);

	/**
	 * @var string $_cursor = self::AGGREGATE_CURSOR
	 */
	private $_cursor = self::AGGREGATE_CURSOR;

	/**
	 * @return string
	 */
	public function &Cursor () {
		return $this->_cursor;
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
	 * @param $item
	 * @param bool $photo = false
	 *
	 * @return SocialNetworkUser
	 */
	private static function _user ($item, $photo = false) {
		if (!$item) return null;

		$user = new SocialNetworkUser($item->id, $item->displayName);

		$user->PhotoFromLink($item->image->url, $photo);
		$user->Page($item->url);
		$user->Bio($item->aboutMe);

		if (isset($item->email)) $user->Email($item->email);
		if ($item->gender == self::GENDER_MALE) $user->Gender(SocialNetworkUser::GENDER_MALE);
		if ($item->gender == self::GENDER_FEMALE) $user->Gender(SocialNetworkUser::GENDER_FEMALE);

		return $user;
	}

	/**
	 * @param $item
	 * @param bool $photo = false
	 *
	 * @return SocialNetworkUser
	 */
	private static function _people ($item, $photo = false) {
		if (!$item) return null;

		$user = new SocialNetworkUser(str_replace('people/', '', $item->resourceName));

		if (isset($item->names[0]->displayName)) $user->Name($item->names[0]->displayName);
		if (isset($item->photos[0]->url)) $user->PhotoFromLink($item->photos[0]->url, $photo);
		if (isset($item->email)) $user->Email($item->email);

		if ($item->gender == self::GENDER_MALE) $user->Gender(SocialNetworkUser::GENDER_MALE);
		if ($item->gender == self::GENDER_FEMALE) $user->Gender(SocialNetworkUser::GENDER_FEMALE);

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
	 *
	 * @return SocialNetworkUser
	 */
	public function SocialNetworkUser ($user) {
		$request = QuarkDTO::ForGET(new QuarkFormIOProcessor());

		$response = $this->OAuthAPI('/plus/v1/people/' . $user, $request);

		return self::_user($response);
	}

	/**
	 * @note requires application approving
	 *
	 * @param string $user
	 * @param int $count
	 * @param int $offset
	 *
	 * @return SocialNetworkUser[]
	 */
	public function SocialNetworkFriends ($user, $count, $offset) {
		$request = QuarkDTO::ForGET(new QuarkFormIOProcessor());
		$request->URIParams(array(
			'pageSize' => $count,
			'pageToken' => $offset
		));

		$response = $this->OAuthAPI('people/' . $user . '/connections', $request, null, self::URL_API_PEOPLE);

		if ($response == null || !isset($response->connections) || !is_array($response->connections)) return array();

		$this->_cursor = $response->nextPageToken;

		$friends = array();

		foreach ($response->connections as $item)
			$friends[] = self::_people($item);

		return $friends;
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