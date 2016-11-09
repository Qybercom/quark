<?php
namespace Quark\Extensions\Quark\MicroService;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;

/**
 * Class MicroServiceConfig
 *
 * @package Quark\Extensions\Quark\MicroService
 */
class MicroServiceConfig implements IQuarkExtensionConfig {
	/**
		 * @var IQuarkMicroServiceProvider $_provider
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
	 * @var string $_name
	 */
	private $_name = '';

	/**
	 * @param IQuarkMicroServiceProvider $provider
	 * @param string $id = ''
	 * @param string $secret = ''
	 */
	public function __construct (IQuarkMicroServiceProvider $provider, $id = '', $secret = '') {
		$this->_provider = $provider;
		$this->appId = $id;
		$this->appSecret = $secret;
	}

	/**
	 * @return IQuarkMicroServiceProvider
	 */
	public function Provider () {
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
	 * @return void
	 */
	public function ExtensionOptions ($ini) {
		if (isset($ini->AppID))
			$this->appId = $ini->AppID;

		if (isset($ini->AppSecret))
			$this->appSecret = $ini->AppSecret;
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new MicroService($this->_name);
	}
}