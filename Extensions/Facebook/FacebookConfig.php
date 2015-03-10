<?php
namespace Quark\Extensions\Facebook;

use Quark\IQuarkExtensionConfig;

/**
 * Class FacebookConfig
 *
 * @package Quark\Extensions\Facebook
 */
class FacebookConfig implements IQuarkExtensionConfig {
	public $appId;
	public $appSecret;

	/**
	 * @param $id
	 * @param $secret
	 */
	public function __construct ($id, $secret) {
		$this->appId = $id;
		$this->appSecret = $secret;
	}

	/**
	 * @return array
	 */
	public function Credentials () {
		return array(
			'appId' => $this->appId,
			'secret' => $this->appSecret
		);
	}
}