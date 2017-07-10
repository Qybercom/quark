<?php
namespace Quark\Extensions\Captcha;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;

/**
 * Class CaptchaConfig
 *
 * @package Quark\Extensions\Captcha
 */
class CaptchaConfig implements IQuarkExtensionConfig {
	/**
	 * @var IQuarkCaptchaProvider $_provider
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
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @param IQuarkCaptchaProvider $provider
	 * @param string $appId = ''
	 * @param string $appSecret = ''
	 */
	public function __construct (IQuarkCaptchaProvider $provider, $appId = '', $appSecret = '') {
		$this->_provider = $provider;
		$this->_appId = $appId;
		$this->_appSecret = $appSecret;

		$this->_provider->CaptchaApplication($this->_appId, $this->_appSecret, null);
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
	 * @return IQuarkCaptchaProvider
	 */
	public function &CaptchaProvider () {
		return $this->_provider;
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

		$this->_provider->CaptchaApplication($this->_appId, $this->_appSecret, $ini);
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new Captcha($this->_name);
	}
}