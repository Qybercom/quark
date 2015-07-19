<?php
namespace Quark\AuthorizationProviders;

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
	 * @param string   $name
	 * @param QuarkDTO $input
	 *
	 * @return bool
	 */
	public function Recognize ($name, QuarkDTO $input) {
		// TODO: Implement Recognize() method.
	}

	/**
	 * @param string   $name
	 * @param QuarkDTO $input
	 * @param bool     $stream
	 *
	 * @return bool|mixed
	 */
	public function Session ($name, QuarkDTO $input, $stream) {
		$user = $input->Header(QuarkDTO::HEADER_AUTHORIZATION);
	}

	/**
	 * @param string     $name
	 * @param QuarkModel $user
	 * @param int        $lifetime (seconds)
	 *
	 * @return QuarkDTO|bool
	 */
	public function Login ($name, QuarkModel $user, $lifetime) {
		// TODO: Implement Login() method.
	}

	/**
	 * @param string $name
	 *
	 * @return QuarkDTO|bool
	 */
	public function Logout ($name) {
		$response = new QuarkDTO();
		$response->Status(QuarkDTO::STATUS_401_UNAUTHORIZED);
		return $response;
	}

	/**
	 * @param string   $name
	 * @param QuarkDTO $input
	 *
	 * @return string
	 */
	public function Signature ($name, QuarkDTO $input) {
		// TODO: Implement Signature() method.
	}
}

/**
 * Class PHPBasicAuth
 *
 * @package Quark\AuthorizationProviders
 */
class PHPBasicAuth {
	/**
	 * @param string   $name
	 * @param QuarkDTO $request
	 * @param          $lifetime
	 *
	 * @return mixed
	 */
	public function Initialize ($name, QuarkDTO $request, $lifetime) {
		if (!isset($_SERVER['PHP_AUTH_USER'])) {
			$response = new QuarkDTO();

			$response->Status(QuarkDTO::STATUS_401_UNAUTHORIZED);
			$response->Header(QuarkDTO::HEADER_WWW_AUTHENTICATE, 'Basic realm="' . $_SERVER['SERVER_NAME'] . '"');

			return $response;
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
		$response = new QuarkDTO();
		$response->Status(QuarkDTO::STATUS_401_UNAUTHORIZED);
		return $response;
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