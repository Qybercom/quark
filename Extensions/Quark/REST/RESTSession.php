<?php
namespace Quark\Extensions\Quark\REST;

use Quark\IQuarkAuthorizableModel;
use Quark\IQuarkAuthorizableModelWithSessionKey;
use Quark\IQuarkAuthorizationProvider;

use Quark\QuarkDTO;
use Quark\QuarkModel;

/**
 * Class RESTSession
 *
 * @package Quark\Extensions\Quark\REST
 */
class RESTSession implements IQuarkAuthorizationProvider {
	/**
	 * @var string $_key
	 */
	private $_key;

	/**
	 * @var IQuarkAuthorizableModel $_user
	 */
	private $_user = '';

	/**
	 * @param IQuarkAuthorizableModel $user
	 *
	 * @return string
	 */
	private function _session (IQuarkAuthorizableModel $user) {
		return $user instanceof IQuarkAuthorizableModelWithSessionKey
			? $user->SessionKey()
			: 'access';
	}

	/**
	 * @param string $name
	 * @param IQuarkAuthorizableModel $user
	 * @param QuarkDTO $input
	 *
	 * @return bool
	 */
	public function Recognize ($name, IQuarkAuthorizableModel $user, QuarkDTO $input) {
		$key = $this->_session($user);

		return isset($input->$key);
	}

	/**
	 * @param string $name
	 * @param IQuarkAuthorizableModel $user
	 * @param QuarkDTO $input
	 * @param bool $http
	 *
	 * @return bool
	 */
	public function Input ($name, IQuarkAuthorizableModel $user, QuarkDTO $input, $http) {
		$this->_key = $this->_session($user);
		$key = $this->_key;

		$this->_user = $user;
		$this->_user->$key = $input->$key;
	}

	/**
	 * @return QuarkDTO
	 */
	public function Output () {
		$key = $this->_key;

		$output = new QuarkDTO();
		$output->$key = $this->_user->$key;

		return $output;
	}

	/**
	 * @param $criteria
	 * @param int $lifetime (seconds)
	 *
	 * @return bool
	 */
	public function Login ($criteria, $lifetime) {
		// TODO: Implement Login() method.
	}

	/**
	 * @param QuarkModel $user
	 *
	 * @return QuarkModel
	 */
	public function User (QuarkModel $user = null) {
		if (func_num_args() != 0)
			$this->_user = $user;

		return $this->_user;
	}

	/**
	 * @return bool
	 */
	public function Logout () {
		// TODO: Implement Logout() method.
	}

	/**
	 * @return string
	 */
	public function Signature () {
		// TODO: Implement Signature() method.
	}
}