<?php
namespace Quark\Extensions\OAuth;

/**
 * Class OAuthClient
 *
 * @package Quark\Extensions\OAuth
 */
class OAuthClient implements IQuarkOAuthConsumer {
	use OAuthConsumerBehavior;

	/**
	 * @param string $config = ''
	 */
	public function __construct ($config = '') {
		if (func_num_args() != 0)
			$this->OAuthConfig($config);
	}
}