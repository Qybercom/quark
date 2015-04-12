<?php
namespace Quark\Extensions\SocialNetwork\Facebook;

use Quark\IQuarkExtension;

use Quark\Quark;

use Facebook\GraphObject;
use Facebook\GraphUser;
use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookCanvasLoginHelper;
use Facebook\FacebookJavaScriptLoginHelper;

/**
 * Class FacebookSession
 *
 * @package Quark\Extensions\SocialNetwork\Facebook
 */
class Facebook implements IQuarkExtension {
	/**
	 * @var FacebookConfig $_config
	 */
	private $_config;

	/**
	 * @var FacebookSession $_session
	 */
	private $_session;

	/**
	 * @param string $config
	 * @param FacebookSession $session
	 */
	public function __construct ($config, $session = null) {
		if (session_status() == PHP_SESSION_NONE)
			session_start();

		$this->_config = Quark::Config()->Extension($config);
		$this->_session = func_num_args() == 1 ? FacebookSession::newAppSession() : $session;

		FacebookSession::setDefaultApplication($this->_config->appId, $this->_config->appSecret);
	}

	/**
	 * @param string $to
	 * @param array  $permissions
	 *
	 * @return string
	 */
	public function LoginURL ($to, $permissions = []) {
		return (new FacebookRedirectLoginHelper($to))->getLoginUrl($permissions);
	}

	/**
	 * @param string $to
	 *
	 * @return string
	 */
	public function LogoutURL ($to) {
		return (new FacebookRedirectLoginHelper(''))->getLogoutUrl($this->_session, $to);
	}

	/**
	 * @return FacebookAccessToken
	 */
	public function Session () {
		return $this->_session != null ? new FacebookAccessToken($this->_session->getAccessToken()) : null;
	}

	/**
	 * @param        $method
	 * @param        $url
	 * @param array  $data
	 * @param string $type = 'Facebook\GraphObject'
	 *
	 * @return GraphObject
	 */
	public function API ($method, $url, $data = [], $type = 'Facebook\GraphObject') {
		try {
			if ($this->_session == null) return null;

			$request = new FacebookRequest($this->_session, $method, $url, $data);

			return $request->execute()->getGraphObject($type);
		}
		catch (FacebookRequestException $e) {
			Quark::Log('FacebookRequestException: ' . $e->getMessage(), Quark::LOG_WARN);
			return null;
		}
		catch (\Exception $e) {
			Quark::Log('Facebook.Exception: ' . $e->getMessage(), Quark::LOG_WARN);
			return null;
		}
	}

	/**
	 * @param string $user = 'me'
	 *
	 * @return GraphObject
	 */
	public function Profile ($user = 'me') {
		return $this->API('GET', '/' . $user, array(), GraphUser::className());
	}

	/**
	 * @param FacebookRedirectLoginHelper|FacebookCanvasLoginHelper|FacebookJavaScriptLoginHelper $helper
	 * @param string $method = 'getSession'
	 *
	 * @return FacebookSession
	 */
	private static function _session ($helper, $method = 'getSession') {
		try {
			return $helper->$method();
		}
		catch (\Exception $e) {
			Quark::Log('Facebook.Exception: ' . $e->getMessage(), Quark::LOG_WARN);
			return null;
		}
	}

	/**
	 * @param string $config
	 * @param string $to
	 *
	 * @return Facebook
	 */
	public static function SessionFromRedirect ($config, $to) {
		return new self($config, self::_session(new FacebookRedirectLoginHelper($to), 'getSessionFromRedirect'));
	}

	/**
	 * @param string $config
	 *
	 * @return Facebook
	 */
	public static function SessionFromCanvas ($config) {
		return new self($config, self::_session(new FacebookCanvasLoginHelper()));
	}

	/**
	 * @param string $config
	 *
	 * @return Facebook
	 */
	public static function SessionFromJavaScript ($config) {
		return new self($config, self::_session(new FacebookJavaScriptLoginHelper()));
	}

	/**
	 * @param string $config
	 * @param string $token
	 *
	 * @return Facebook
	 */
	public static function SessionFromToken ($config, $token) {
		return new self($config, new FacebookSession($token));
	}
}