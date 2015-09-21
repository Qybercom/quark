<?php
namespace Quark\Extensions\SocialNetwork\Providers;

use Quark\Extensions\SocialNetwork\IQuarkSocialNetworkProvider;

/**
 * Class Odnoklassniki
 *
 * @package Quark\Extensions\SocialNetwork\Providers
 */
class Odnoklassniki implements IQuarkSocialNetworkProvider {
	/**
	 * @param string $appId
	 * @param string $appSecret
	 *
	 * @return mixed
	 */
	public function Init ($appId, $appSecret) {
		// TODO: Implement Init() method.
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
	 *
	 * @return mixed
	 */
	public function SessionFromRedirect ($to) {
		// TODO: Implement SessionFromRedirect() method.
	}

	/**
	 * @param string $token
	 *
	 * @return mixed
	 */
	public function SessionFromToken ($token) {
		// TODO: Implement SessionFromToken() method.
	}

	/**
	 * @param $user
	 *
	 * @return mixed
	 */
	public function Profile ($user) {
		// TODO: Implement Profile() method.
	}

	/**
	 * @return mixed
	 */
	public function API () {
		// TODO: Implement API() method.
	}
}