<?php
namespace Quark\AuthorizationProviders;

use Quark\IQuarkAuthorizationProvider;

use Quark\QuarkDTO;
use Quark\QuarkModel;

/**
 * Class PHPBasicAuth
 *
 * @package Quark\AuthorizationProviders
 */
class PHPBasicAuth implements IQuarkAuthorizationProvider {
	private static $_user;

	/**
	 * @param string $msg
	 *
	 * @return string
	 */
	public static function Error401 ($msg = 'Unauthorized') {
		header('HTTP/1.0 401 ' . $msg);
	}

	/**
	 * @param string   $name
	 * @param QuarkDTO $request
	 * @param          $lifetime
	 *
	 * @return mixed
	 */
	public function Initialize ($name, QuarkDTO $request, $lifetime) {
		if (!isset($_SERVER['PHP_AUTH_USER']) && self::$_user == null) {
			self::Error401();
			header('WWW-Authenticate: Basic realm="' . $_SERVER['SERVER_NAME'] . '"');
			return null;
		}
		else return array(
			'username' => $_SERVER['PHP_AUTH_USER'],
			'password' => $_SERVER['PHP_AUTH_PW']
		);
	}

	/**
	 * @param string     $name
	 * @param QuarkDTO   $response
	 * @param QuarkModel $user
	 *
	 * @return mixed
	 */
	public function Trail ($name, QuarkDTO $response, QuarkModel $user) {
		// TODO: Implement Trail() method.
	}

	/**
	 * @param string     $name
	 * @param QuarkModel $model
	 * @param            $criteria
	 *
	 * @return bool
	 */
	public function Login ($name, QuarkModel $model, $criteria) {
		// TODO: Implement Login() method.
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function Logout ($name) {
		self::Error401();
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	public function Signature ($name) {
		// TODO: Implement Signature() method.
	}
}