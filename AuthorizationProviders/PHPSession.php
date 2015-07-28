<?php
namespace Quark\AuthorizationProviders;

use Quark\IQuarkAuthorizationProvider;

use Quark\Quark;
use Quark\QuarkCookie;
use Quark\QuarkKeyValuePair;
use Quark\QuarkModel;
use Quark\QuarkDTO;

/**
 * Class PHPSession
 *
 * @package Quark\AuthorizationProviders
 */
class PHPSession implements IQuarkAuthorizationProvider {
	/**
	 * @var QuarkDTO $_output
	 */
	private $_output;

	/**
	 * start session
	 */
	private function _start () {
		if (session_status() == PHP_SESSION_NONE)
			@session_start();
	}

	/**
	 * @param string $name
	 * @param string $id
	 * @param int $lifetime (seconds)
	 *
	 * @return bool
	 */
	private function _end ($name, $id, $lifetime) {
		$this->_output = new QuarkDTO();
		$this->_output->Cookie(new QuarkCookie(session_name(), $id, $lifetime));
		$this->_output->AuthorizationProvider(new QuarkKeyValuePair($name, $id));

		session_write_close();

		return true;
	}

	/**
	 * @param string $name
	 * @param QuarkDTO $input
	 *
	 * @return bool
	 */
	public function Recognize ($name, QuarkDTO $input) {
		return $input->GetCookieByName(session_name()) != null;
	}

	/**
	 * @param string $name
	 * @param QuarkDTO $input
	 * @param bool $http
	 *
	 * @return bool|mixed
	 */
	public function Input ($name, QuarkDTO $input, $http) {
		if (isset($_COOKIE[session_name()]))
			unset($_COOKIE[session_name()]);

		$session = $http
			? $input->GetCookieByName(session_name())
			: ($input->AuthorizationProvider() != null ? $input->AuthorizationProvider()->ToCookie() : null);

		if (!$session || !$session->value || !is_string($session->value)) return false;

		$session->value = trim($session->value);

		if (!preg_match('/^[a-zA-Z0-9,\-]{22,40}$/', $session->value)) return false;

		session_id($session->value);
		$this->_start();

		if (!isset($_SESSION[$name]['user'])) return false;

		$this->_output = new QuarkDTO();
		$this->_output->AuthorizationProvider(new QuarkKeyValuePair($name, $session->value));

		return $_SESSION[$name]['user'];
	}

	/**
	 * @param string $name
	 * @param QuarkModel $user
	 * @param int $lifetime (seconds)
	 *
	 * @return bool
	 */
	public function Login ($name, QuarkModel $user, $lifetime) {
		/**
		 * http://stackoverflow.com/a/8311400/2097055
		 */
		ini_set('session.gc_maxlifetime', $lifetime);
		ini_set('session.auto_start', false);

		session_regenerate_id();
		$this->_start();

		$_SESSION[$name]['user'] = $user->Model();
		$_SESSION[$name]['signature'] = Quark::GuID();

		return $this->_end($name, session_id(), $lifetime);
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

		return $this->_end($name, session_id(), -3600);
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	public function Signature ($name) {
		return $this->_start() && isset($_SESSION[$name]['signature']) ? $_SESSION[$name]['signature'] : '';
	}

	/**
	 * @param string $name
	 *
	 * @return QuarkDTO
	 */
	public function Output ($name) {
		session_write_close();
		return $this->_output;
	}
}