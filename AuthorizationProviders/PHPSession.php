<?php
namespace Quark\AuthorizationProviders;

use Quark\IQuarkAuthorizationProvider;
use Quark\IQuarkAuthorizableModel;

use Quark\Quark;
use Quark\QuarkModel;

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
	 * @var PHPSession $_session;
	 */
	private static $_session;

	/**
	 * @param $request
	 * @return mixed
	 */
	public function Initialize ($request) {
		@session_start();

		if (!isset($_SESSION) || !isset($_SESSION['user'])) return null;

		self::$_user = self::$_model->RenewSession($this, Quark::Normalize(new \StdClass(), $_SESSION['user']));
	}

	/**
	 * @param $response
	 * @return mixed
	 */
	public function Trail ($response) { }

	/**
	 * @return IQuarkAuthorizationProvider
	 */
	public static function Instance () {
		return self::$_session;
	}

	/**
	 * @param IQuarkAuthorizableModel $model
	 *
	 * @return IQuarkAuthorizationProvider
	 */
	public static function Setup (IQuarkAuthorizableModel $model) {
		self::$_model = $model;

		return self::$_session = new PHPSession();
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

		$_SESSION['user'] = self::$_user instanceof QuarkModel ? self::$_user->Model() : self::$_user;
		$_SESSION['signature'] = Quark::GuID();

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

	/**
	 * @return string
	 */
	public static function Signature () {
		return isset($_SESSION['signature']) ? $_SESSION['signature'] : '';
	}
}