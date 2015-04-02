<?php
namespace Quark\AuthorizationProviders;

use Quark\IQuarkAuthorizableModelWithSessionKey;
use Quark\IQuarkAuthorizationProvider;

use Quark\QuarkDTO;
use Quark\QuarkModel;

/**
 * Class CookieAuth
 *
 * @package Quark\AuthorizationProviders
 */
class CookieAuth implements IQuarkAuthorizationProvider {
	private $_lifetime = 0;

	/**
	 * @param string   $name
	 * @param QuarkDTO $request
	 * @param          $lifetime
	 *
	 * @return mixed
	 */
	public function Initialize ($name, QuarkDTO $request, $lifetime) {
		$this->_lifetime = $lifetime;

		return array(
			'session' => $request->Cookies()[$name]
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
		return $response;
	}

	/**
	 * @param string     $name
	 * @param QuarkModel $model
	 * @param            $criteria
	 *
	 * @return bool
	 */
	public function Login ($name, QuarkModel $model, $criteria) {
		$model = $model->Model();

		if (!($model instanceof IQuarkAuthorizableModelWithSessionKey)) return false;

		$key = $model->SessionKey();

		return setcookie($name, $model->$key, $this->_lifetime);
	}

	/**
	 * http://stackoverflow.com/a/686166/2097055
	 * @param string $name
	 *
	 * @return bool
	 */
	public function Logout ($name) {
		return setcookie($name, '', 1);
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