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
	 * @return array
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
		// TODO: Implement Stacked() method.
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		// TODO: Implement ExtensionInstance() method.
	}
}