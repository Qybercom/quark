<?php
namespace Quark\Extensions\SocialNetwork\Providers;

use Quark\QuarkDTO;
use Quark\QuarkFormIOProcessor;

use Quark\Extensions\OAuth\IQuarkOAuthConsumer;
use Quark\Extensions\OAuth\IQuarkOAuthProvider;
use Quark\Extensions\OAuth\OAuthToken;
use Quark\Extensions\OAuth\Providers\YandexOAuth;

use Quark\Extensions\SocialNetwork\IQuarkSocialNetworkProvider;
use Quark\Extensions\SocialNetwork\SocialNetwork;
use Quark\Extensions\SocialNetwork\SocialNetworkUser;
use Quark\Extensions\SocialNetwork\SocialNetworkPost;
use Quark\Extensions\SocialNetwork\SocialNetworkPublishingChannel;
use Quark\Extensions\SocialNetwork\SocialNetworkProviderBehavior;

/**
 * Class YandexMoyKrug
 *
 * @package Quark\Extensions\SocialNetwork\Providers
 */
class YandexMoyKrug extends YandexOAuth implements IQuarkOAuthProvider, IQuarkSocialNetworkProvider {
	const URL_PASSPORT = 'https://login.yandex.ru/info';

	const GENDER_MALE = 'male';
	const GENDER_FEMALE = 'female';

	const CURRENT_USER = 'me';

	const AGGREGATE_COUNT = 100;
	const AGGREGATE_CURSOR = '';

	const PHOTO_SMALL = 'islands-small';
	const PHOTO_34 = 'islands-34';
	const PHOTO_MIDDLE = 'islands-middle';
	const PHOTO_50 = 'islands-50 — 50×50';
	const PHOTO_RETINA_SMALL = 'islands-retina-small';
	const PHOTO_68 = 'islands-68';
	const PHOTO_75 = 'islands-75';
	const PHOTO_RETINA_MIDDLE = 'islands-retina-middle';
	const PHOTO_RETINA_50 = 'islands-retina-50';
	const PHOTO_200 = 'islands-200';

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
	 * @param $item
	 * @param bool $photo = false
	 *
	 * @return SocialNetworkUser
	 */
	private static function _user ($item, $photo = false) {
		if (!$item) return null;

		$user = new SocialNetworkUser($item->id, $item->real_name);

		if (isset($item->default_avatar_id)) $user->PhotoFromLink(self::URL_AVATAR . $item->default_avatar_id . '/' . self::PHOTO_MIDDLE, $photo);
		if (isset($item->birthday)) $user->BirthdayByDate('Y-m-d', $item->birthday);
		if (isset($item->login)) $user->Username($item->login);
		if (isset($item->default_email)) $user->Email($item->default_email);

		if (isset($item->sex)) {
			if ($item->sex == self::GENDER_MALE) $user->Gender(SocialNetworkUser::GENDER_MALE);
			if ($item->sex == self::GENDER_FEMALE) $user->Gender(SocialNetworkUser::GENDER_FEMALE);
		}

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
		$request->URIParams(array(
			'format' => 'json'
		));

		$response = $this->OAuthAPI('', $request, null, self::URL_PASSPORT);

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
		return array();
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