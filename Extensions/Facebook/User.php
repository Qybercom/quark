<?php
namespace Quark\Extensions\Facebook;

use Quark\IQuarkExtension;
use Quark\Quark;
use Quark\QuarkArchException;

/**
 * Class User
 * @package Quark\Extensions\Facebook
 */
class User implements IQuarkExtension {
	private static $_facebook;
	private static $_session;
	private static $_token;

	/**
	 * @param Config|null $config
	 * @throws QuarkArchException
	 * @return mixed
	 */
	public static function providers ($config) {
		$facebook = Quark::NormalizePath(__DIR__ . '/SDK/src/facebook.php', false);
		$facebook_base = Quark::NormalizePath(__DIR__ . '/SDK/src/base_facebook.php', false);

		$arch = new QuarkArchException('Facebook SDK integrity violation. Please add facebook-php-sdk as git submodule to SDK directory');

		if (!is_file($facebook) || !is_file($facebook_base)) throw $arch;

		include_once $facebook;
		include_once $facebook_base;

		if (!class_exists('Facebook') || !class_exists('BaseFacebook')) throw $arch;

		self::$_facebook = new \Facebook($config->Credentials());
		self::$_session = self::$_facebook->getUser();
	}

	/**
	 * @param string|null $token
	 * @return string
	 */
	public static function Session ($token = null) {
		if (self::$_token == null)
			self::$_token = self::$_facebook->getAccessToken();

		if ($token != null)
			self::$_token = $token;

		Quark::Log('token: [' . self::$_token . ']');

		return self::$_token;
	}

	/**
	 * @param string $type
	 * @param string $method
	 * @param array $params
	 * @return mixed
	 */
	private static function _api ($type, $method, $params = []) {
		if (!is_array($params)) $params = array();

		$params += array(
			'access_token' => self::$_token
		);

		$query = $method . '?' . http_build_query($params);

		Quark::Log('API call: [' . $query . ']', Quark::LOG_INFO, 'Facebook');

		try {
			return self::$_facebook->api($query, $type);
		}
		catch (\Exception $e) {
			Quark::Log('API exception: ' . print_r($e, true), Quark::LOG_WARN, 'Facebook');
			return null;
		}
	}

	/**
	 * @param string $id
	 * @return mixed
	 */
	public static function Profile ($id = 'me') {
		return self::_api('GET', '/' . $id);
	}

	public static function Friends () {}

	/**
	 * @return mixed
	 */
	public static function Login () {
		return self::$_facebook->getLoginUrl();
	}

	/**
	 * @return mixed
	 */
	public static function Logout () {
		return self::$_facebook->getLogoutUrl();
	}
}