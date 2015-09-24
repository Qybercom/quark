<?php
namespace Quark\Extensions\SocialNetwork\Providers;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkHTTPTransportClient;
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
	const PERMISSION_PICTURE = 'picture';
	const PERMISSION_GENDER = 'gender';
	const PERMISSION_LINK = 'link';

	const PERMISSION_EMAIL = 'email';
	const PERMISSION_BIRTHDAY = 'birthday';

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
	 * @return mixed
	 */
	public function Init ($appId, $appSecret) {
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

		return $response == null ? '' : $this->_session = $response->access_token;
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
	 * @param string $user
	 * @param string[] $fields
	 *
	 * @return SocialNetworkUser
	 */
	public function Profile ($user, $fields = []) {
		$response = $this->API('GET', '/' . $user, array('fields' => implode(',', array(
			self::PERMISSION_ID,
			self::PERMISSION_NAME,
			self::PERMISSION_PICTURE,
			self::PERMISSION_GENDER,
			self::PERMISSION_LINK,
			self::PERMISSION_EMAIL,
			self::PERMISSION_BIRTHDAY
		))));

		if ($response == null) return null;

		$user = new SocialNetworkUser($response->id, $response->name);

		$user->AccessToken($this->_session);
		$user->PhotoFromLink('http://graph.facebook.com/' . $response->id . '/picture?width=1200&height=1200'); // $response->picture->data->url);
		$user->Gender($response->gender[0]);
		$user->Page($response->link);

		if (isset($response->email))
			$user->Email($response->email);

		if (isset($response->birthday))
			$user->BirthdayByDate('m/d/Y', $response->birthday);

		return $user;
	}

	/**
	 * @param string $method
	 * @param string $url
	 * @param array  $data
	 * @param string $base = 'https://graph.facebook.com/'
	 *
	 * @return QuarkDTO|\StdClass
	 */
	public function API ($method = '', $url = '', $data = [], $base = 'https://graph.facebook.com/') {
		$request = new QuarkDTO(new QuarkJSONIOProcessor());
		$request->Method($method);

		$get = $method == 'GET';

		if (!$get)
			$request->Data($data);

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		$out = QuarkHTTPTransportClient::To($base . $url . '?' . http_build_query(array_merge_recursive($get ? $data : array()) + array(
			'access_token' => $this->_session
		)), $request, $response);

		if (!$out->Data()) {
			$data = array();
			parse_str($out->RawData(), $data);

			$out->Data((object)$data);
		}

		if (isset($out->error)) {
			Quark::Log('Facebook.Exception: ' . $out->error->type . ': ' . $out->error->message . '. Code: ' . $out->error->code, Quark::LOG_WARN);
			return null;
		}

		return $out;
	}

	/**
	 * @return string
	 */
	public function CurrentUser () {
		return '/me';
	}
}