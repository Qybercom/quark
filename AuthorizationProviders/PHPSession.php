<?php
namespace Quark\AuthorizationProviders;

use Quark\IQuarkAuthorizationProvider;

use Quark\Quark;
use Quark\QuarkCookie;
use Quark\QuarkModel;
use Quark\QuarkDTO;

/**
 * Class PHPSession
 *
 * @package Quark\AuthorizationProviders
 */
class PHPSession implements IQuarkAuthorizationProvider {
	const DEFAULT_NAME = 'PHPSESSID';

	/**
	 * @var QuarkDTO $_request
	 */
	private $_request;

	/**
	 * @param QuarkDTO $request
	 * http://stackoverflow.com/a/22373561
	 */
	public function _start (QuarkDTO $request = null) {
		if (func_num_args() != 0)
			$this->_request = $request;

		if ($this->_request == null)
			$this->_request = new QuarkDTO();

		$session = $this->_request->GetCookieByName(session_name());

		if ($session == null) {
			@session_start();
			$session = new QuarkCookie(session_name(), session_id());
		}

		if (!preg_match('/^[a-zA-Z0-9,\-]{22,40}$/', $session->value))
			unset($_COOKIE[session_name()]);

		if (session_status() == PHP_SESSION_NONE) @session_start();
	}

	/**
	 * @param QuarkDTO $request
	 *
	 * @return string|null
	 *
	 * http://stackoverflow.com/a/22373561
	 */
	private function _init (QuarkDTO $request = null) {
		if (func_num_args() != 0)
			$this->_request = $request;

		$id = $this->_request->GetCookieByName(session_name());

		if ($id == null) return null;
		if (!preg_match('/^[a-zA-Z0-9,\-]{22,40}$/', $id->value)) return null;

		session_id($id->value);
		session_start();

		return $id->value;
	}

	/**
	 * @param string   $name
	 * @param QuarkDTO $request
	 * @param          $lifetime
	 *
	 * @return mixed
	 */
	public function Initialize ($name, QuarkDTO $request, $lifetime) {
		$this->_start($request);

		if (!isset($_SESSION) || !isset($_SESSION[$name]) || !isset($_SESSION[$name]['user'])) return null;

		/**
		 * http://stackoverflow.com/a/8311400/2097055
		 */
		ini_set('session.gc_maxlifetime', $lifetime);
		ini_set('session.auto_start', false);

		session_set_cookie_params($lifetime);

		return $_SESSION[$name]['user'];
	}

	/**
	 * @param string $name
	 * @param QuarkDTO $response
	 * @param QuarkModel $user
	 *
	 * @return mixed
	 */
	public function Trail ($name, QuarkDTO $response, QuarkModel $user) {
		session_write_close();
	}

	/**
	 * @param string $name
	 * @param QuarkModel $model
	 * @param $credentials
	 *
	 * @return bool
	 */
	public function Login ($name, QuarkModel $model, $credentials) {
		$this->_start();

		session_regenerate_id(true);

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
		$this->_start();

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
		$this->_start();

		return isset($_SESSION[$name]['signature']) ? $_SESSION[$name]['signature'] : '';
	}
}