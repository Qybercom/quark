<?php
namespace Quark\Extensions\Quark\REST;

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
	 * @var QuarkDTO $_output
	 */
	private $_output;

	/**
	 * @param QuarkModel $user
	 *
	 * @return string
	 */
	private function _key (QuarkModel $user = null) {
		if (!$user) return null;

		$model = $user->Model();

		return $model instanceof IQuarkAuthorizableModelWithSessionKey
			? $model->SessionKey()
			: 'access';
	}

	/**
	 * @param string $name
	 * @param QuarkModel $user
	 * @param QuarkDTO $input
	 *
	 * @return bool
	 */
	public function Recognize ($name, QuarkModel $user, QuarkDTO $input) {
		$key = $this->_key($user);

		return isset($input->$key);
	}

	/**
	 * @param string $name
	 * @param QuarkModel $user
	 * @param QuarkDTO $input
	 * @param bool $http
	 *
	 * @return bool|mixed
	 */
	public function Input ($name, QuarkModel $user, QuarkDTO $input, $http) {
		$this->_output = $input;
	}

	/**
	 * @param string $name
	 * @param QuarkModel $user
	 *
	 * @return QuarkDTO
	 */
	public function Output ($name, QuarkModel $user) {
		$key = $this->_key($user);
		$this->_output->$key = $user->$key;

		return $this->_output;
	}

	/**
	 * @param string $name
	 * @param QuarkModel $user
	 * @param int $lifetime (seconds)
	 *
	 * @return bool
	 */
	public function Login ($name, QuarkModel $user, $lifetime) {
		// TODO: Implement Login() method.
	}

	/**
	 * @param string $name
	 * @param QuarkModel $user
	 *
	 * @return bool
	 */
	public function Logout ($name, QuarkModel $user) {
		// TODO: Implement Logout() method.
	}

	/**
	 * @param string $name
	 * @param QuarkModel $user
	 *
	 * @return string
	 */
	public function Signature ($name, QuarkModel $user) {
		// TODO: Implement Signature() method.
	}
}