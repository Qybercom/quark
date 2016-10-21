<?php
namespace Quark\Extensions\SocialNetwork\Providers;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;

use Quark\Extensions\SocialNetwork\IQuarkSocialNetworkProvider;

use Quark\Extensions\SocialNetwork\SocialNetworkUser;

/**
 * Class FacebookSession
 *
 * @package Quark\Extensions\SocialNetwork\Providers
 */
class Facebook implements IQuarkSocialNetworkProvider {
	const CURRENT_USER = '/me';

	const PERMISSION_ID = 'id';
	const PERMISSION_NAME = 'name';
	const PERMISSION_PICTURE = 'picture.width(1200).height(1200)';
	const PERMISSION_GENDER = 'gender';
	const PERMISSION_LINK = 'link';

	const PERMISSION_EMAIL = 'email';
	const PERMISSION_BIRTHDAY = 'birthday';
	const PERMISSION_FRIENDS = 'user_friends';

	const PERMISSION_LIKES = 'user_likes';
	const PERMISSION_PUBLISH_ACTIONS = 'publish_actions';

	private $_appId = '';
	private $_appSecret = '';

	/**
	 * @var string $_session
	 */
	private $_session;

	/**
	 * @return string
	 */
	public function Name () {
		return 'Facebook';
	}

	/**
	 * @param string $appId
	 * @param string $appSecret
	 *
	 * @return void
	 */
	public function SocialNetworkApplication ($appId, $appSecret) {
		$this->_appId = $appId;
		$this->_appSecret = $appSecret;
	}

	/**
	 * @param string $to
	 * @param string[] $permissions
	 *
	 * @return string
	 */
	public function LoginURL ($to, $permissions = []) {
		return 'https://www.facebook.com/v2.3/dialog/oauth?' . http_build_query(array(
			'client_id' => $this->_appId,
			'redirect_uri' => $to,
			'state' => Quark::GuID(),
			'scope' => implode(',', (array)$permissions)
		));
	}

	/**
	 * @param string $to
	 *
	 * @return string
	 */
	public function LogoutURL ($to) {
		return 'https://www.facebook.com/logout.php?' . http_build_query(array(
			'next' => $to,
			'access_token' => $this->_session
		));
	}

	/**
	 * @param string $to
	 * @param string $code
	 *
	 * @return string
	 */
	public function SessionFromRedirect ($to, $code) {
		$response = $this->API('GET', '/oauth/access_token', array(
			'client_id' => $this->_appId,
			'client_secret' => $this->_appSecret,
			'redirect_uri' => $to,
			'code' => $code
		));

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
		return '/me';
	}

	/**
	 * @param string $method
	 * @param string $url
	 * @param array  $data
	 * @param string $base = 'https://graph.facebook.com/'
	 *
	 * @return QuarkDTO|\stdClass
	 */
	public function API ($method = '', $url = '', $data = [], $base = 'https://graph.facebook.com/') {
		$request = new QuarkDTO(new QuarkJSONIOProcessor());
		$request->Method($method);

		$get = $method == 'GET';

		if (!$get)
			$request->Data($data);

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		$out = QuarkHTTPClient::To($base . $url . '?' . http_build_query(array_merge_recursive($get ? $data : array()) + array(
					'access_token' => $this->_session
				)), $request, $response);

		if (!$out->Data()) {
			$data = array();
			parse_str($out->RawData(), $data);

			$out->Data((object)$data);
		}

		if (isset($out->error)) {
			Quark::Log('Facebook.Exception: '
				. (isset($out->error->type) ? $out->error->type : '')
				. ': '
				. (isset($out->error->message) ? $out->error->message : '')
				. '. Code: ' . (isset($out->error->code) ? $out->error->code : '')
				, Quark::LOG_WARN);

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
	private static function _fields ($fields) {
		return implode(',', $fields === null ? array(
			self::PERMISSION_ID,
			self::PERMISSION_NAME,
			self::PERMISSION_LINK,
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
		$user = new SocialNetworkUser($item->id, $item->name);

		$user->PhotoFromLink($item->picture->data->url, $photo);
		$user->Gender($item->gender[0]);
		$user->Page($item->link);

		if (isset($item->email))
			$user->Email($item->email);

		if (isset($item->birthday))
			$user->BirthdayByDate('m/d/Y', $item->birthday);

		return $user;
	}

	/**
	 * @param string $user
	 * @param string[] $fields
	 *
	 * @return SocialNetworkUser
	 */
	public function Profile ($user, $fields) {
		$response = $this->API('GET', '/' . $user, array('fields' => self::_fields($fields)));

		if ($response == null) return null;

		return self::_user($response);
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
		$response = $this->API('GET', '/' . $user . '/friends', array('fields' => self::_fields($fields)));

		if ($response == null) return array();

		$friends = array();

		foreach ($response->data as $item)
			$friends[] = self::_user($item, false);

		return $friends;
	}
}