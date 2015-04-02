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
	 * @param string   $name
	 * @param QuarkDTO $request
	 * @param          $lifetime
	 *
	 * @return mixed
	 */
	public function Initialize ($name, QuarkDTO $request, $lifetime) {
		return $request;
	}

	/**
	 * @param string   $name
	 * @param QuarkDTO $response
	 * @param QuarkModel $user
	 *
	 * @return mixed
	 */
	public function Trail ($name, QuarkDTO $response, QuarkModel $user) {
		$model = $user->Model();
		$key = $model instanceof IQuarkAuthorizableModelWithSessionKey ? $model->SessionKey() : 'access';

		return $user == null ? array() : array($key => $user->$key);
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
		// TODO: Implement Logout() method.
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