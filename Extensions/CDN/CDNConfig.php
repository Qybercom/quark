<?php
namespace Quark\Extensions\CDN;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;

/**
 * Class CDNConfig
 *
 * @package Quark\Extensions\CDN
 */
class CDNConfig implements IQuarkExtensionConfig {
	/**
	 * @var IQuarkCDNProvider $_provider
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
	 * @param IQuarkCDNProvider $provider
	 * @param string $id = ''
	 * @param string $secret = ''
	 */
	public function __construct (IQuarkCDNProvider $provider, $id = '', $secret = '') {
		$this->_provider = $provider;
		$this->appId = $id;
		$this->appSecret = $secret;

		$this->_provider->CDNApplication($this->appId, $this->appSecret);
	}

	/**
	 * @return object
	 */
	public function Credentials () {
		return (object)array(
			'appId' => $this->appId,
			'secret' => $this->appSecret
		);
	}

	/**
	 * @return IQuarkCDNProvider
	 */
	public function &CDNProvider () {
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
		// TODO: Implement ExtensionOptions() method.
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		// TODO: Implement ExtensionInstance() method.
	}
}