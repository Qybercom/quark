<?php
namespace Quark\Extensions\Facebook;

use Quark\IQuarkExtensionConfig;

/**
 * Class Config
 *
 * @package Quark\Extensions\Facebook
 */
class Config implements IQuarkExtensionConfig {
	private $_id;
	private $_secret;

	/**
	 * @param $id
	 * @param $secret
	 */
	public function __construct ($id, $secret) {
		$this->_id = $id;
		$this->_secret = $secret;
	}

	/**
	 * @return array
	 */
	public function Credentials () {
		return array(
			'appId' => $this->_id,
			'secret' => $this->_secret
		);
	}
}