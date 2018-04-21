<?php
namespace Quark\Extensions\OAuth;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;

use Quark\QuarkDTO;

/**
 * Class OAuthConfig
 *
 * @package Quark\Extensions\OAuth
 */
class OAuthConfig implements IQuarkExtensionConfig {
	const URL_AUTHORIZE = 'authorize';
	const URL_TOKEN = 'token';

	const RESPONSE_CODE = 'code';
	const RESPONSE_TOKEN = 'token';
	const RESPONSE_DEVICE_CODE = 'device_code';

	const GRANT_AUTHORIZATION_CODE = 'authorization_code';
	const GRANT_PASSWORD = 'password';
	const GRANT_CLIENT_CREDENTIALS = 'client_credentials';
	const GRANT_REFRESH_TOKEN = 'refresh_token';
	const GRANT_DEVICE_CODE = 'device_code';

	/**
	 * @var IQuarkOAuthProvider $_provider
	 */
	private $_provider;

	/**
	 * @var string $_appId = ''
	 */
	private $_appId = '';

	/**
	 * @var string $_appSecret = ''
	 */
	private $_appSecret = '';

	/**
	 * @var object $_options
	 */
	private $_options;

	/**
	 * @var string $_name
	 */
	private $_name = '';

	/**
	 * @param IQuarkOAuthProvider $provider
	 * @param string $appId = ''
	 * @param string $appSecret = ''
	 * @param object $options = null
	 */
	public function __construct (IQuarkOAuthProvider $provider, $appId = '', $appSecret = '', $options = null) {
		$this->_provider = $provider;
		$this->_appId = $appId;
		$this->_appSecret = $appSecret;
		$this->_options = (object)$options;
	}

	/**
	 * @return IQuarkOAuthProvider
	 */
	public function &Provider () {
		return $this->_provider;
	}

	/**
	 * @param OAuthToken $token
	 *
	 * @return IQuarkOAuthConsumer
	 */
	public function Consumer (OAuthToken $token) {
		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$this->_provider->OAuthApplication($this->_appId, $this->_appSecret, $this->_options);

		$consumer = $this->_provider->OAuthConsumer($token);

		$consumer->OAuthToken($token);
		$consumer->OAuthProvider($this->_provider);

		return $consumer;
	}

	/**
	 * @return string
	 */
	public function &AppID () {
		return $this->_appId;
	}

	/**
	 * @return string
	 */
	public function &AppSecret () {
		return $this->_appSecret;
	}

	/**
	 * @return object
	 */
	public function &Options () {
		return $this->_options;
	}

	/**
	 * @param string $name
	 */
	public function Stacked ($name) {
		$this->_name = $name;
	}

	/**
	 * @return string
	 */
	public function ExtensionName () {
		return $this->_name;
	}

	/**
	 * @param object $ini
	 *
	 * @return mixed
	 */
	public function ExtensionOptions ($ini) {
		if (isset($ini->AppID))
			$this->_appId = $ini->AppID;

		if (isset($ini->AppSecret))
			$this->_appSecret = $ini->AppSecret;

		$this->_options = $ini;

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$this->_provider->OAuthApplication($this->_appId, $this->_appSecret, $this->_options);
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		// TODO: Implement ExtensionInstance() method.
	}

	/**
	 * @return string[]
	 */
	public static function URLS () {
		return array(
			self::URL_AUTHORIZE,
			self::URL_TOKEN
		);
	}

	/**
	 * @param QuarkDTO $request
	 *
	 * @return string|null
	 */
	public static function URLAllowed (QuarkDTO $request) {
		if ($request->URI() == null) return null;

		$url = $request->URI()->ReverseRoute(0);

		return in_array($url, self::URLS()) ? $url : null;
	}
}