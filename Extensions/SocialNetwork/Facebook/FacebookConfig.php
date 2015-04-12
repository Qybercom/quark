<?php
namespace Quark\Extensions\SocialNetwork\Facebook;

use Quark\IQuarkExtensionConfig;

use Quark\Quark;

/**
 * Class FacebookConfig
 *
 * @package Quark\Extensions\SocialNetwork\Facebook
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

		Quark::Import(__DIR__ . '/facebook-php-sdk-v4/src/');
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