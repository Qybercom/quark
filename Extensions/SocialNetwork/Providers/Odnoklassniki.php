<?php
namespace Quark\Extensions\SocialNetwork\Providers;

use Quark\Extensions\SocialNetwork\IQuarkSocialNetworkProvider;
use Quark\Extensions\SocialNetwork\SocialNetworkUser;

/**
 * Class Odnoklassniki
 *
 * @package Quark\Extensions\SocialNetwork\Providers
 */
class Odnoklassniki implements IQuarkSocialNetworkProvider {
	/**
	 * @return string
	 */
	public function Name () {
		// TODO: Implement Name() method.
	}

	/**
	 * @param string $appId
	 * @param string $appSecret
	 *
	 * @return mixed
	 */
	public function SocialNetworkApplication ($appId, $appSecret) {
		// TODO: Implement SocialNetworkApplication() method.
	}

	/**
	 * @param string $to
	 * @param string[] $permissions
	 *
	 * @return string
	 */
	public function LoginURL ($to, $permissions = []) {
		// TODO: Implement LoginURL() method.
	}

	/**
	 * @param string $to
	 *
	 * @return string
	 */
	public function LogoutURL ($to) {
		// TODO: Implement LogoutURL() method.
	}

	/**
	 * @param string $to
	 * @param string $code
	 *
	 * @return string
	 */
	public function SessionFromRedirect ($to, $code) {
		// TODO: Implement SessionFromRedirect() method.
	}

	/**
	 * @param string $token
	 *
	 * @return string
	 */
	public function SessionFromToken ($token) {
		// TODO: Implement SessionFromToken() method.
	}

	/**
	 * @return string
	 */
	public function CurrentUser () {
		// TODO: Implement CurrentUser() method.
	}

	/**
	 * @return \Quark\QuarkDTO
	 */
	public function API () {
		// TODO: Implement API() method.
	}

	/**
	 * @param string $user
	 * @param string[] $fields
	 *
	 * @return SocialNetworkUser
	 */
	public function Profile ($user, $fields) {
		// TODO: Implement Profile() method.
	}

	/**
	 * @param string $user
	 * @param string[] $fields
	 * @param int $count
	 * @param int $offset
	 *
	 * @return SocialNetworkUser[]
	 */
	public function Friends ($user, $fields, $count, $offset) {
		// TODO: Implement Friends() method.
	}
}