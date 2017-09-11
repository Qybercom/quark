<?php
namespace Quark\Extensions\OAuth;

use Quark\IQuarkAuthorizableModel;

/**
 * Interface IQuarkOAuthAuthorizableModel
 *
 * @package Quark\Extensions\OAuth
 */
interface IQuarkOAuthAuthorizableModel extends IQuarkAuthorizableModel {
	/**
	 * @return OAuthToken
	 */
	public function OAuthModelSuccess();

	/**
	 * @return OAuthError
	 */
	public function OAuthModelError();
}