<?php
namespace Quark\Extensions\Feedly;

use Quark\IQuarkExtensionConfig;

use Quark\Extensions\OAuth\OAuthConfig;

/**
 * Class FeedlyConfig
 *
 * @package Quark\Extensions\Feedly
 */
class FeedlyConfig extends OAuthConfig implements IQuarkExtensionConfig {
	/**
	 * @param string $appId = ''
	 * @param string $appSecret = ''
	 */
	public function __construct ($appId = '', $appSecret = '') {
		parent::__construct(new FeedlyAPI(), $appId, $appSecret);
	}
}