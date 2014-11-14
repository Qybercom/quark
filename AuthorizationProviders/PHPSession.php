<?php
namespace Quark\AuthorizationProviders;

use Quark\IQuarkAuthorizationProvider;
use Quark\IQuarkAuthorizableModel;

/**
 * Class PHPSession
 *
 * @package Quark\AuthorizationProviders
 */
class PHPSession implements IQuarkAuthorizationProvider {
	/**
	 * @var IQuarkAuthorizableModel $_model;
	 */
	private static $_model;
	private static $_user;

	/**
	 * @param $request
	 * @return mixed
	 */
	public function Initialize ($request) {
		@session_start();

		if (!isset($_SESSION)) return null;

		self::$_user = self::$_model->RenewSession($this, $_SESSION);
	}

	/**
	 * @param $response
	 * @return mixed
	 */
	public function Trail ($response) { }

	/**
	 * @param IQuarkAuthorizableModel $model
	 *
	 * @return IQuarkAuthorizationProvider
	 */
	public static function Setup (IQuarkAuthorizableModel $model) {
		self::$_model = $model;

		return new PHPSession();
	}

	/**
	 * @param IQuarkAuthorizableModel $model
	 * @param $credentials
	 *
	 * @return bool
	 */
	public static function Login (IQuarkAuthorizableModel $model, $credentials) {
		self::$_user = $model->Authorize($credentials);

		if (!self::$_user) return false;

		@session_start();

		$_SESSION['user'] = self::$_user;

		return true;
	}

	/**
	 * @return IQuarkAuthorizableModel
	 */
	public static function User () {
		return self::$_user;
	}

	/**
	 * @return bool
	 */
	public static function Logout () {
		@session_start();

		if (!isset($_SESSION['user'])) return false;

		self::$_user = null;
		unset($_SESSION['user']);
		session_destroy();

		return true;
	}
}