<?php
namespace Quark\Extensions\OAuth;

/**
 * Trait OAuthViewBehavior
 *
 * @package Quark\Extensions\OAuth
 */
trait OAuthViewBehavior {
	/**
	 * @param string $config
	 * @param string $redirect = ''
	 * @param string[] $scope = []
	 *
	 * @return string|null
	 */
	public function OAuthLoginURL ($config = '', $redirect = '', $scope = []) {
		$token = new OAuthToken($config);

		return $token->Consumer()->OAuthLoginURL($redirect, $scope);
	}
}