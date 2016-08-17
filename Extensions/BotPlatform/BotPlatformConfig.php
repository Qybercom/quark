<?php
namespace Quark\Extensions\BotPlatform;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;

/**
 * Class BotPlatformConfig
 *
 * @package Quark\Extensions\BotPlatform
 */
class BotPlatformConfig implements IQuarkExtensionConfig {
	/**
	 * @var IQuarkBotPlatformProvider $_provider
	 */
	private $_provider;

	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var string $appId
	 */
	public $appId = '';

	/**
	 * @var string $appSecret
	 */
	public $appSecret = '';

	/**
	 * @param IQuarkBotPlatformProvider $provider
	 * @param string $id = ''
	 * @param string $secret = ''
	 */
	public function __construct (IQuarkBotPlatformProvider $provider, $id = '', $secret = '') {
		$this->_provider = $provider;
		$this->appId = $id;
		$this->appSecret = $secret;
	}

	/**
	 * @return IQuarkBotPlatformProvider
	 */
	public function &BotPlatformProvider () {
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
			$this->appId = $ini->AppID;

		if (isset($ini->AppSecret))
			$this->appSecret = $ini->AppSecret;

		$this->_provider->BotApplication($this->appId, $this->appSecret);
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new BotPlatform($this->_name);
	}
}