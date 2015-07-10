<?php
namespace Quark\AuthorizationProviders;

use Quark\IQuarkAuthorizationProvider;

use Quark\IQuarkAuthorizationProvider2;
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
	private $_id;

	/**
	 * @var QuarkCookie $_session
	 */
	private $_session;

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
	 * @param int|double $lifetime
	 *
	 * @return bool
	 *
	 * http://stackoverflow.com/a/22373561
	 */
	private function _init (QuarkDTO $request = null, $lifetime = 0) {
		if (func_num_args() != 0)
			$this->_session = $request->GetCookieByName(session_name());

		if ($this->_session == null) return false;
		if (!preg_match('/^[a-zA-Z0-9,\-]{22,40}$/', $this->_session->value)) return false;

		session_id($this->_session->value);

		if (func_num_args() != 0)
			$this->_session->Lifetime($lifetime);

		$start = true;

		if (session_status() == PHP_SESSION_NONE)
			$start = session_start();

		return $start;
	}

	/**
	 * @param string   $name
	 * @param QuarkDTO $request
	 * @param          $lifetime
	 *
	 * @return mixed
	 */
	public function Initialize ($name, QuarkDTO $request, $lifetime) {
		/**
		 * http://stackoverflow.com/a/8311400/2097055
		 */
		ini_set('session.gc_maxlifetime', $lifetime);
		ini_set('session.auto_start', false);

		if (!$this->_init($request) || !isset($_SESSION[$name]) || !isset($_SESSION[$name]['user'])) return null;

		return $_SESSION[$name]['user'];
	}

	/**
	 * @param string   $name
	 * @param QuarkDTO $request
	 * @param          $lifetime
	 *
	 * @return mixed
	 */
	public function Initialize1 ($name, QuarkDTO $request, $lifetime) {
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
		return $response->Cookie($this->_session);
	}

	/**
	 * @param string $name
	 * @param QuarkDTO $response
	 * @param QuarkModel $user
	 *
	 * @return mixed
	 */
	public function Trail1 ($name, QuarkDTO $response, QuarkModel $user) {
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
		if (session_status() == PHP_SESSION_NONE)
			if (!session_start()) return false;

		$_SESSION[$name]['user'] = $model->Model();
		$_SESSION[$name]['signature'] = Quark::GuID();

		return true;
	}

	/**
	 * @param string $name
	 * @param QuarkModel $model
	 * @param $credentials
	 *
	 * @return bool
	 */
	public function Login1 ($name, QuarkModel $model, $credentials) {
		$this->_start();

		//session_regenerate_id(true);

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

/**
 * Class PHPSession2
 *
 * @package Quark\AuthorizationProviders
 */
class PHPSession2 implements IQuarkAuthorizationProvider2 {
	/**
	 * @return bool
	 */
	private function _start () {
		$start = true;

		if (session_status() == PHP_SESSION_NONE)
			$start = session_start();

		return $start;
	}

	/**
	 * @param string $id
	 * @param int $lifetime (seconds)
	 *
	 * @return QuarkDTO
	 */
	private function _end ($id, $lifetime) {
		$output = new QuarkDTO();
		$output->Cookie(new QuarkCookie(session_name(), $id, $lifetime));
		return $output;
	}

	/**
	 * @param string $name
	 * @param QuarkDTO $input
	 *
	 * @return string
	 */
	public function SessionId ($name, QuarkDTO $input) {
		$session = $input->GetCookieByName(session_name());

		return $session ? $session->value : false;
	}

	/**
	 * @param string $name
	 * @param string $id
	 *
	 * @return mixed
	 */
	public function Session ($name, $id) {
		if (!preg_match('/^[a-zA-Z0-9,\-]{22,40}$/', $id)) return false;

		session_id($id);

		return $this->_start();
	}

	/**
	 * @param string $name
	 * @param QuarkModel $user
	 * @param int $lifetime (seconds)
	 *
	 * @return QuarkDTO|bool
	 */
	public function Login ($name, QuarkModel $user, $lifetime) {
		/**
		 * http://stackoverflow.com/a/8311400/2097055
		 */
		ini_set('session.gc_maxlifetime', $lifetime);
		ini_set('session.auto_start', false);

		if (!$this->_start()) return false;

		$_SESSION[$name]['user'] = $user->Model();
		$_SESSION[$name]['signature'] = Quark::GuID();

		return $this->_end(session_id(), $lifetime);
	}

	/**
	 * @param string $name
	 *
	 * @return QuarkDTO|bool
	 */
	public function Logout ($name) {
		if (!$this->_start() || !isset($_SESSION[$name])) return false;

		unset($_SESSION[$name]);

		if (sizeof($_SESSION) == 0)
			session_destroy();

		return $this->_end('', -3600);
	}
}