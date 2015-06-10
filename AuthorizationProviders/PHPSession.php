<?php
namespace Quark\AuthorizationProviders;

use Quark\IQuarkAuthorizationProvider;

use Quark\Quark;
use Quark\QuarkModel;
use Quark\QuarkDTO;

/**
 * Class PHPSession
 *
 * @package Quark\AuthorizationProviders
 */
class PHPSession implements IQuarkAuthorizationProvider {
	/**
	 * @param string   $name
	 * @param QuarkDTO $request
	 * @param          $lifetime
	 *
	 * @return mixed
	 */
	public function Initialize ($name, QuarkDTO $request, $lifetime) {
		unset($_COOKIE[session_name()]);

		if (session_status() == PHP_SESSION_NONE)
			session_start();

		if (!isset($_SESSION) || !isset($_SESSION[$name]) || !isset($_SESSION[$name]['user'])) return null;

		/**
		 * http://stackoverflow.com/a/8311400/2097055
		 */
		ini_set('session.gc_maxlifetime', $lifetime);
		session_set_cookie_params($lifetime);
		session_regenerate_id(true);

		return $_SESSION[$name]['user'];
	}

	/**
	 * @param string $name
	 * @param QuarkDTO $response
	 * @param QuarkModel $user
	 *
	 * @return mixed
	 */
	public function Trail ($name, QuarkDTO $response, QuarkModel $user) { }

	/**
	 * @param string $name
	 * @param QuarkModel $model
	 * @param $credentials
	 *
	 * @return bool
	 */
	public function Login ($name, QuarkModel $model, $credentials) {
		if (session_status() == PHP_SESSION_NONE)
			session_start();

		$_SESSION[$name]['user'] = $model->Model();
		$_SESSION[$name]['signature'] = Quark::GuID();

		return true;
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function Logout ($name) {
		if (session_status() == PHP_SESSION_NONE)
			session_start();

		if (!isset($_SESSION[$name])) return false;

		unset($_SESSION[$name]);

		if (sizeof($_SESSION) == 0)
			session_destroy();

		return true;
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	public function Signature ($name) {
		return isset($_SESSION[$name]['signature']) ? $_SESSION[$name]['signature'] : '';
	}
}