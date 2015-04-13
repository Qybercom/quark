<?php
namespace Quark\Extensions\SocialNetwork\Providers;

use Quark\Quark;
use Quark\QuarkClient;
use Quark\QuarkDTO;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkHTTPTransport;
use Quark\QuarkJSONIOProcessor;

use Quark\Extensions\SocialNetwork\IQuarkSocialNetworkProvider;

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

	private $_session;

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
			'v' => '5.29',
			'scope' => implode(',', (array)$permissions),
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
	 *
	 * @return mixed
	 */
	public function SessionFromRedirect ($to) {
		$client = new QuarkClient(
			'https://oauth.vk.com/access_token?' . http_build_query(array(
				'client_id' => $this->_appId,
				'client_secret' => $this->_appSecret,
				'code' => $_GET['code'],
				'redirect_uri' => $to,
			)),
			new QuarkHTTPTransport(
				QuarkDTO::ForGET(),
				new QuarkDTO(new QuarkJSONIOProcessor())
			));

		$response = $client->Action();

		if (isset($response->error) || !isset($response->access_token)) {
			Quark::Log('VKontakte.Exception: ' . $response->error . ': ' . $response->error_description, Quark::LOG_WARN);
			return null;
		}

		return $this->_session = $response->access_token;
	}

	/**
	 * @param string $token
	 *
	 * @return mixed
	 */
	public function SessionFromToken ($token) {
		return $this->_session = $token;
	}

	/**
	 * @param $user
	 *
	 * @return mixed
	 */
	public function Profile ($user) {
		$response = $this->API('GET', 'users.get')->response;

		return is_array($response) && sizeof($response) != 0 ? $response[0] : null;
	}

	/**
	 * @param string $method
	 * @param string $url
	 * @param array  $data
	 *
	 * @return mixed
	 */
	public function API ($method = '', $url = '', $data = []) {
		$request = new QuarkDTO(new QuarkFormIOProcessor());
		$request->Method($method);
		$request->Data($data);

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		$client = new QuarkClient(
			'https://api.vk.com/method/' . $url . '?' . http_build_query(($method == 'GET' ? $data : array()) + array(
				'access_token' => $this->_session
			)),
			new QuarkHTTPTransport($request, $response)
		);

		$out = $client->Action();

		if (isset($out->error)) {
			Quark::Log('VKontakte.Exception: ' . $out->error->error_code . ': ' . $out->error->error_msg, Quark::LOG_WARN);
			return null;
		}

		return $out;
	}
}