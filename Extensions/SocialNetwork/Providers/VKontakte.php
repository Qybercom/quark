<?php
namespace Quark\Extensions\SocialNetwork\Providers;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkHTTPTransportClient;
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

	const PERMISSION_NOTIFY = 'notify';
	const PERMISSION_FRIENDS = 'friends';
	const PERMISSION_PHOTOS = 'photos';
	const PERMISSION_AUDIO = 'audio';
	const PERMISSION_VIDEO = 'video';
	const PERMISSION_DOCS = 'docs';
	const PERMISSION_NOTES = 'notes';
	const PERMISSION_PAGES = 'pages';
	const PERMISSION_LINK = 'link';
	const PERMISSION_STATUS = 'status';
	const PERMISSION_OFFERS = 'offers';
	const PERMISSION_QUESTIONS = 'questions';
	const PERMISSION_WALL = 'wall';
	const PERMISSION_GROUPS = 'groups';
	const PERMISSION_MESSAGES = 'messages';
	const PERMISSION_EMAIL = 'email';
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
	 * @var string $_current
	 */
	private $_current = '';

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

		$this->_session = $response->access_token;
		$this->_current = $response->user_id;

		return $this->_session;
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
	 * @param $user
	 *
	 * @return SocialNetworkUser
	 */
	public function Profile ($user) {
		$response = $this->API('GET', 'users.get')->response;
		$response = is_array($response) && sizeof($response) != 0 ? $response[0] : null;

		if ($response == null) return null;

		$user = new SocialNetworkUser($response->id, $response->name);

		$user->AccessToken($this->_session);
		$user->Gender($response->gender[0]);
		$user->PhotoFromLink($response->picture->data->url);
		$user->Page($response->link);

		return $user;
	}

	/**
	 * @param string $method
	 * @param string $url
	 * @param array  $data
	 * @param string $base = 'https://api.vk.com/method/'
	 *
	 * @return QuarkDTO
	 */
	public function API ($method = '', $url = '', $data = [], $base = 'https://api.vk.com/method/') {
		$request = new QuarkDTO(new QuarkFormIOProcessor());
		$request->Method($method);
		$request->Data($data);

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		$out = QuarkHTTPTransportClient::To($base . $url . '?' . http_build_query(($method == 'GET' ? $data : array()) + array(
			'access_token' => $this->_session
		)), $request, $response);

		if (isset($out->error)) {
			Quark::Log('VKontakte.Exception: ' . $out->error->error_code . ': ' . $out->error->error_msg, Quark::LOG_WARN);
			return null;
		}

		return $out;
	}

	/**
	 * @return string
	 */
	public function CurrentUser () {
		return $this->_current;
	}
}