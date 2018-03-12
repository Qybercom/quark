<?php
namespace Quark\Extensions\SMS;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;

/**
 * Class SMSConfig
 *
 * @package Quark\Extensions\SMS
 */
class SMSConfig implements IQuarkExtensionConfig {
	/**
	 * @var IQuarkSMSProvider $_provider
	 */
	private $_provider;

	/**
	 * @var string $_appID = ''
	 */
	private $_appID = '';

	/**
	 * @var string $_appSecret = ''
	 */
	private $_appSecret = '';

	/**
	 * @var string $_appName = ''
	 */
	private $_appName = '';

	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @param IQuarkSMSProvider $provider
	 * @param string $appID = ''
	 * @param string $appSecret = ''
	 * @param string $appName = null
	 */
	public function __construct (IQuarkSMSProvider $provider, $appID = '', $appSecret = '', $appName = null) {
		$this->Provider($provider);
		$this->AppID($appID);
		$this->AppSecret($appSecret);
		$this->AppName($appName);
	}

	/**
	 * @param IQuarkSMSProvider $provider = null
	 *
	 * @return IQuarkSMSProvider
	 */
	public function &Provider (IQuarkSMSProvider $provider = null) {
		if ($provider != null)
			$this->_provider = $provider;

		return $this->_provider;
	}

	/**
	 * @param string $appID = ''
	 *
	 * @return string
	 */
	public function AppID ($appID = '') {
		if (func_num_args() != 0)
			$this->_appID = $appID;

		return $this->_appID;
	}

	/**
	 * @param string $appSecret = ''
	 *
	 * @return string
	 */
	public function AppSecret ($appSecret = '') {
		if (func_num_args() != 0)
			$this->_appSecret = $appSecret;

		return $this->_appSecret;
	}

	/**
	 * @param string $appName = null
	 *
	 * @return string
	 */
	public function AppName ($appName = null) {
		if (func_num_args() != 0)
			$this->_appName = $appName;

		return $this->_appName;
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
			$this->AppID($ini->AppID);

		if (isset($ini->AppSecret))
			$this->AppSecret($ini->AppSecret);

		if (isset($ini->AppName))
			$this->AppName($ini->AppName);

		$this->_provider->SMSProviderApplication($this->_appID, $this->_appSecret, $this->_appName);
		$this->_provider->SMSProviderOptions($ini);
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new SMS($this->_name);
	}
}