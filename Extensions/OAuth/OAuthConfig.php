<?php
namespace Quark\Extensions\OAuth;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;

/**
 * Class OAuthConfig
 *
 * @package Quark\Extensions\OAuth
 */
class OAuthConfig implements IQuarkExtensionConfig {
	const RESPONSE_CODE = 'code';
	const RESPONSE_TOKEN = 'token';

	const GRANT_AUTHORIZATION_CODE = 'authorization_code';
	const GRANT_PASSWORD = 'password';
	const GRANT_CLIENT_CREDENTIALS = 'client_credentials';

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
	 * @var string $_name
	 */
	private $_name = '';

	/**
	 * @param IQuarkOAuthProvider $provider
	 * @param string $appId = ''
	 * @param string $appSecret = ''
	 */
	public function __construct (IQuarkOAuthProvider $provider, $appId = '', $appSecret = '') {
		$this->_provider = $provider;
		$this->_appId = $appId;
		$this->_appSecret = $appSecret;
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
		$this->_provider->OAuthApplication($this->_appId, $this->_appSecret);

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

		$this->_provider->OAuthApplication($this->_appId, $this->_appSecret);
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		// TODO: Implement ExtensionInstance() method.
	}
}