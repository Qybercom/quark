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
		// TODO: Implement Stacked() method.
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		// TODO: Implement ExtensionInstance() method.
	}
}