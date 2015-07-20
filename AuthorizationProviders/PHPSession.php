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
	 * @return bool
	 */
	public function Recognize ($name, QuarkDTO $input) {
		return $input->GetCookieByName(session_name()) != null;
	}

	/**
	 * @param string $name
	 * @param QuarkDTO $input
	 * @param bool $stream
	 *
	 * @return bool|mixed
	 */
	public function Session ($name, QuarkDTO $input, $stream) {
		$session = $stream
			? $input->AuthorizationProvider()->ToCookie()
			: $input->GetCookieByName(session_name());

		if (!$session || !preg_match('/^[a-zA-Z0-9,\-]{22,40}$/', $session->value)) return false;

		session_id($session->value);

		if (!$this->_start()) return false;

		return isset($_SESSION[$name]['user']) ? $_SESSION[$name]['user'] : false;
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

		session_regenerate_id();

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

		return $this->_end(session_id(), -3600);
	}

	/**
	 * @param string $name
	 * @param QuarkDTO $input
	 *
	 * @return string
	 */
	public function Signature ($name, QuarkDTO $input) {
		return $this->_start() && isset($_SESSION[$name]['signature']) ? $_SESSION[$name]['signature'] : '';
	}
}