<?php
namespace Quark\Extensions\SocialNetwork\Providers;

use Quark\Quark;
use Quark\QuarkDate;
use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkFormIOProcessor;

use Quark\Extensions\SocialNetwork\IQuarkSocialNetworkProvider;

use Quark\Extensions\SocialNetwork\SocialNetworkUser;

/**
 * Class VKontakte
 *
 * @package Quark\Extensions\SocialNetwork\Providers
 */
class VKontakte implements IQuarkSocialNetworkProvider {
	const CURRENT_USER = '';

	const PERMISSION_ID = 'uid';
	const PERMISSION_NAME = '';
	const PERMISSION_PICTURE = 'photo_max_orig';
	const PERMISSION_GENDER = 'sex';
	const PERMISSION_LINK = 'link';

	const PERMISSION_EMAIL = 'email';
	const PERMISSION_BIRTHDAY = 'bdate';

	const PERMISSION_NOTIFY = 'notify';
	const PERMISSION_FRIENDS = 'friends';
	const PERMISSION_PHOTOS = 'photos';
	const PERMISSION_AUDIO = 'audio';
	const PERMISSION_VIDEO = 'video';
	const PERMISSION_DOCS = 'docs';
	const PERMISSION_NOTES = 'notes';
	const PERMISSION_PAGES = 'pages';
	const PERMISSION_STATUS = 'status';
	const PERMISSION_OFFERS = 'offers';
	const PERMISSION_QUESTIONS = 'questions';
	const PERMISSION_WALL = 'wall';
	const PERMISSION_GROUPS = 'groups';
	const PERMISSION_MESSAGES = 'messages';
	const PERMISSION_NOTIFICATIONS = 'notifications';
	const PERMISSION_STATS = 'stats';
	const PERMISSION_ADS = 'ads';

	const PERMISSION_OFFLINE = 'offline';
	const PERMISSION_NOHTTPS = 'nohttps';

	private $_appId = '';
	private $_appSecret = '';

	/**
	 * @var string $_session
	 */
	private $_session;

	/**
	 * @var string[] $_gender
	 */
	private static $_gender = array(
		SocialNetworkUser::GENDER_UNKNOWN,
		SocialNetworkUser::GENDER_FEMALE,
		SocialNetworkUser::GENDER_MALE
	);

	/**
	 * @return string
	 */
	public function Name () {
		return 'VKontakte';
	}

	/**
	 * @param string $appId
	 * @param string $appSecret
	 *
	 * @return mixed
	 */
	public function Init ($appId, $appSecret) {
		$this->_appId = $appId;
		$this->_appSecret = $appSecret;
	}

	/**
	 * @param string $to
	 * @param array  $permissions
	 *
	 * @return string
	 */
	public function LoginURL ($to, $permissions = []) {
		return 'https://oauth.vk.com/authorize?' . http_build_query(array(
			'client_id' => $this->_appId,
			'redirect_uri' => $to,
			'state' => Quark::GuID(),
			'scope' => implode(',', (array)$permissions),
			'v' => '5.29',
			'response_type' => 'code'
		));
	}

	/**
	 * @param string $to
	 *
	 * @return string
	 */
	public function LogoutURL ($to) {
		// TODO: Implement LogoutURL() method.
	}

	/**
	 * @param string $to
	 * @param string $code
	 *
	 * @return string
	 */
	public function SessionFromRedirect ($to, $code) {
		$response = $this->API('GET', '/access_token', array(
			'client_id' => $this->_appId,
			'client_secret' => $this->_appSecret,
			'redirect_uri' => $to,
			'code' => $code), 'https://oauth.vk.com/');

		if ($response == null) return '';

		return $this->_session = $response->access_token;
	}

	/**
	 * @param string $token
	 *
	 * @return string
	 */
	public function SessionFromToken ($token) {
		return $this->_session = $token;
	}

	/**
	 * @return string
	 */
	public function CurrentUser () {
		return '';
	}

	/**
	 * @param string $method
	 * @param string $url
	 * @param array  $data
	 * @param string $base = 'https://api.vk.com/method/'
	 *
	 * @return QuarkDTO|\StdClass
	 */
	public function API ($method = '', $url = '', $data = [], $base = 'https://api.vk.com/method/') {
		$request = new QuarkDTO(new QuarkFormIOProcessor());
		$request->Method($method);

		$get = $method == 'GET';

		if (!$get)
			$request->Data($data);

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		$out = QuarkHTTPClient::To($base . $url . '?' . http_build_query(array_merge_recursive($get ? $data : array()) + array(
			'access_token' => $this->_session
		)), $request, $response);

		if (isset($out->error)) {
			Quark::Log('VKontakte.Exception: '
				. (isset($out->error->error_code) ? $out->error->error_code : '')
				. ': '
				. (isset($out->error->error_msg) ? $out->error->error_msg : ''),
				Quark::LOG_WARN);

			Quark::Trace($out);

			return null;
		}

		return $out;
	}

	/**
	 * @param string[] $fields
	 *
	 * @return string
	 */
	private static function _fields ($fields = []) {
		return implode(',', $fields === null ? array(
			self::PERMISSION_GENDER,
			self::PERMISSION_PICTURE,
			self::PERMISSION_BIRTHDAY,
			self::PERMISSION_EMAIL
		) : $fields);
	}

	/**
	 * @param $item
	 * @param bool $photo = true
	 *
	 * @return SocialNetworkUser
	 */
	private static function _user ($item, $photo = true) {
		if (!$item) return null;

		$user = new SocialNetworkUser($item->uid, $item->first_name . ' ' . $item->last_name);

		$user->PhotoFromLink($item->photo_max_orig, $photo);
		$user->Gender(isset(self::$_gender[$item->sex]) ? self::$_gender[$item->sex] : SocialNetworkUser::GENDER_UNKNOWN);
		$user->Page('http://vk.com/id' . $item->uid);

		if (isset($item->email))
			$user->Email($item->email);

		if (isset($item->bdate)) {
			$date = explode('.', $item->bdate);
			$out = array();

			foreach ($date as $component)
				$out[] = (strlen($component == 1) ? '0' : '') . $component;

			if (sizeof($out) == 2)
				$out[] = QuarkDate::UNKNOWN_YEAR;

			$user->BirthdayByDate('d.m.Y', implode('.', $out), 'd.m');
		}

		return $user;
	}

	/**
	 * @param string $user
	 * @param string[] $fields
	 *
	 * @return SocialNetworkUser
	 */
	public function Profile ($user, $fields) {
		$response = $this->API('GET', 'users.get', array(
			'user_ids' => $user,
			'scope' => 'email',
			'fields' => self::_fields($fields)
		));

		if (!$response || !is_array($response->response)) return null;

		return self::_user(isset($response->response[0]) ? $response->response[0] : null);
	}

	/**
	 * @param string $user
	 * @param string[] $fields
	 * @param int $count
	 * @param int $offset
	 *
	 * @return SocialNetworkUser[]
	 */
	public function Friends ($user, $fields, $count, $offset) {
		$response = $this->API('GET', 'friends.get', array(
			'user_id' => $user,
			'scope' => 'email',
			'fields' => self::_fields($fields),
			'count' => $count,
			'offset' => $offset
		));

		if (!$response || !is_array($response->response)) return array();

		$friends = array();

		foreach ($response->response as $item)
			$friends[] = self::_user($item);

		return $friends;
	}
}