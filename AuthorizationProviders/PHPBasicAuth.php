<?php
namespace Quark\AuthorizationProviders;

use Quark\IQuarkAuthorizationProvider;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkModel;

/**
 * Class PHPBasicAuth
 *
 * @package Quark\AuthorizationProviders
 */
class PHPBasicAuth implements IQuarkAuthorizationProvider {
	/**
	 * @param string   $name
	 * @param QuarkDTO $request
	 * @param          $lifetime
	 *
	 * @return mixed
	 */
	public function Initialize ($name, QuarkDTO $request, $lifetime) {
		if (!isset($_SERVER['PHP_AUTH_USER'])) {
			Quark::HTTPStatus(QuarkDTO::STATUS_401_UNAUTHORIZED);
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
		Quark::HTTPStatus(QuarkDTO::STATUS_401_UNAUTHORIZED);
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