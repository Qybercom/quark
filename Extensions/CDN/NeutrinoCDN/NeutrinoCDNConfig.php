<?php
namespace Quark\Extensions\CDN\NeutrinoCDN;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;

/**
 * Class NeutrinoCDNConfig
 *
 * @package Quark\Extensions\CDN\NeutrinoCDN
 */
class NeutrinoCDNConfig implements IQuarkExtensionConfig {
	/**
	 * @var string $appId = ''
	 */
	public $appId = '';

	/**
	 * @var string $appSecret = ''
	 */
	public $appSecret = '';

	/**
	 * @param string $id
	 * @param string $secret
	 */
	public function __construct ($id, $secret) {
		$this->appId = $id;
		$this->appSecret = $secret;
	}

	/**
	 * @return string
	 */
	public function BaseURL () {
		return 'http://ncdn/';
	}

	/**
	 * @param string $resource = ''
	 * @param string $action = NeutrinoCDN::ACTION_GET
	 *
	 * @return string
	 */
	public function ResourceURL ($resource = '', $action = NeutrinoCDN::ACTION_GET) {
		return $this->BaseURL() . '/resource/' . $action . $resource;
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