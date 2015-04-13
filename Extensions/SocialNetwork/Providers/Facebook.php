<?php
namespace Quark\Extensions\SocialNetwork\Providers;

use Quark\Quark;

use Quark\Extensions\SocialNetwork\IQuarkSocialNetworkProvider;

use Facebook\GraphObject;
use Facebook\GraphUser;
use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;
use Facebook\FacebookRedirectLoginHelper;

/**
 * Class FacebookSession
 *
 * @package Quark\Extensions\SocialNetwork\Providers
 */
class Facebook implements IQuarkSocialNetworkProvider {
	const CURRENT_USER = '/me';

	private $_appId = '';
	private $_appSecret = '';

	/**
	 * @var FacebookSession $_session
	 */
	private $_session;

	/**
	 * Facebook constructor
	 */
	public function __construct () {
		Quark::Import(__DIR__ . '/facebook-php-sdk-v4/src/');
	}

	/**
	 * @param string $method
	 * @param string $url
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
	 * @param string $user
	 *
	 * @return GraphObject
	 */
	public function Profile ($user) {
		return $this->API('GET', '/' . $user, array(), GraphUser::className())->asArray();
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

		if (session_status() == PHP_SESSION_NONE)
			session_start();

		FacebookSession::setDefaultApplication($appId, $appSecret);
		$this->_session = FacebookSession::newAppSession($appId, $appSecret);
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
	 * @param FacebookSession $session
	 *
	 * @return null|string
	 */
	private function _session (FacebookSession $session = null) {
		$this->_session = $session;
		return $this->_session == null ? null : (string)$this->_session->getAccessToken();
	}

	/**
	 * @param string $to
	 *
	 * @return mixed
	 */
	public function SessionFromRedirect ($to) {
		try {
			return $this->_session((new FacebookRedirectLoginHelper($to))->getSessionFromRedirect());
		}
		catch (\Exception $e) {
			Quark::Log('SocialNetwork.Facebook exception: ' . $e->getMessage(), Quark::LOG_WARN);
			return null;
		}
	}

	/**
	 * @param string $token
	 *
	 * @return mixed
	 */
	public function SessionFromToken ($token) {
		return $this->_session(new FacebookSession($token));
	}
}