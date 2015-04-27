<?php
namespace Quark\Extensions\SMS;

use Quark\IQuarkAuthorizationProvider;

use Quark\QuarkDTO;
use Quark\QuarkModel;

/**
 * Class SMSAuth
 *
 * @package Quark\Extensions\SMS
 */
class SMSAuth implements IQuarkAuthorizationProvider {
	/**
	 * @param string $name
	 * @param QuarkDTO $request
	 * @param $lifetime
	 *
	 * @return mixed
	 */
	public function Initialize ($name, QuarkDTO $request, $lifetime) {
		// TODO: Implement Initialize() method.
	}

	/**
	 * @param string $name
	 * @param QuarkDTO $response
	 * @param QuarkModel $user
	 *
	 * @return mixed
	 */
	public function Trail ($name, QuarkDTO $response, QuarkModel $user) {
		// TODO: Implement Trail() method.
	}

	/**
	 * @param string $name
	 * @param QuarkModel $model
	 * @param $criteria
	 *
	 * @return bool
	 */
	public function Login ($name, QuarkModel $model, $criteria) {
		$sms = new SMS($name);

		$sms->Message($criteria->text);
		$sms->Phone($criteria->phone);

		return $sms->Cost();
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