<?php
namespace Quark\AuthorizationProviders;

use Quark\IQuarkAuthorizableModel;
use Quark\IQuarkAuthorizationProvider;
use Quark\QuarkModel;

class PHPBasicAuth implements IQuarkAuthorizationProvider {
	/**
	 * @var IQuarkAuthorizableModel $_model
	 */
	private static $_model;
	private static $_user;

	/**
	 * @param $request
	 *
	 * @return mixed
	 */
	public function Initialize ($request) {
		if (!isset($_SERVER['PHP_AUTH_USER'])) return null;

		self::$_user = self::$_model->RenewSession($this, array(
			'username' => $_SERVER['PHP_AUTH_USER'],
			'password' => $_SERVER['PHP_AUTH_PW']
		));
	}

	/**
	 * @param $response
	 *
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

		return new PHPBasicAuth();
	}

	/**
	 * @param IQuarkAuthorizableModel $model
	 * @param                         $credentials
	 *
	 * @return bool
	 */
	public static function Login (IQuarkAuthorizableModel $model, $credentials) {
		if (isset($_SERVER['PHP_AUTH_USER'])) return true;

		header('WWW-Authenticate: Basic realm="' . $_SERVER['SERVER_NAME'] . '"');
		header('HTTP/1.0 401 Unauthorized');

		self::$_user = $model->Authorize(array(
			'username' => isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : '',
			'password' => isset($_SERVER['PHP_AUTH_PW'])  ? $_SERVER['PHP_AUTH_PW'] : ''
		));

		if (!self::$_user) return false;

		return true;
	}

	public static function Unauthorized () {
		//header('HTTP/1.0 401 Unauthorized');
		header('HTTP/1.0 403 Access denied');
	}

	/**
	 * @return QuarkModel
	 */
	public static function User () {
		return self::$_user;
	}

	/**
	 * @return bool
	 */
	public static function Logout () {
		return true;
	}
}