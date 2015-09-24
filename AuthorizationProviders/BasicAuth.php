<?php
namespace Quark\AuthorizationProviders;

use Quark\IQuarkAuthorizableModel;
use Quark\IQuarkAuthorizationProvider;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkModel;

/**
 * Class BasicAuth
 *
 * @package Quark\AuthorizationProviders
 */
class BasicAuth implements IQuarkAuthorizationProvider {
	/**
	 * @param string $name
	 * @param IQuarkAuthorizableModel $user
	 * @param QuarkDTO $input
	 *
	 * @return bool
	 */
	public function Recognize ($name, IQuarkAuthorizableModel $user, QuarkDTO $input) {
		// TODO: Implement Recognize() method.
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
		// TODO: Implement Input() method.
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
		// TODO: Implement User() method.
	}

	/**
	 * @return bool
	 */
	public function Logout () {
		// TODO: Implement Logout() method.
	}

	/**
	 * @return QuarkDTO
	 */
	public function Output () {
		// TODO: Implement Output() method.
	}

	/**
	 * @return string
	 */
	public function Signature () {
		// TODO: Implement Signature() method.
	}
}