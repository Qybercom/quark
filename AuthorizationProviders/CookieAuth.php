<?php
namespace Quark\AuthorizationProviders;

use Quark\IQuarkAuthorizableModel;
use Quark\IQuarkAuthorizationProvider;

use Quark\QuarkModel;

/**
 * Class CookieAuth
 *
 * @package Quark\AuthorizationProviders
 */
class CookieAuth implements IQuarkAuthorizationProvider {
	/**
	 * @param $request
	 *
	 * @return mixed
	 */
	public function Initialize ($request) {
		// TODO: Implement Initialize() method.
	}

	/**
	 * @param $response
	 *
	 * @return mixed
	 */
	public function Trail ($response) {
		// TODO: Implement Trail() method.
	}

	/**
	 * @return IQuarkAuthorizationProvider
	 */
	public static function Instance () {
		// TODO: Implement Instance() method.
	}

	/**
	 * @param IQuarkAuthorizableModel $model
	 *
	 * @return IQuarkAuthorizationProvider
	 */
	public static function Setup (IQuarkAuthorizableModel $model) {
		// TODO: Implement Setup() method.
	}

	/**
	 * @param IQuarkAuthorizableModel $model
	 * @param                         $credentials
	 *
	 * @return bool
	 */
	public static function Login (IQuarkAuthorizableModel $model, $credentials) {
		// TODO: Implement Login() method.
	}

	/**
	 * @return QuarkModel
	 */
	public static function User () {
		// TODO: Implement User() method.
	}

	/**
	 * @return bool
	 */
	public static function Logout () {
		// TODO: Implement Logout() method.
	}

	/**
	 * @return string
	 */
	public static function Signature () {
		// TODO: Implement Signature() method.
	}
}