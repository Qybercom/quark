<?php
namespace Quark\AuthorizationProviders;

use Quark\IQuarkAuthorizableModel;
use Quark\IQuarkAuthorizationProvider;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkKeyValuePair;

/**
 * Class BasicAuth
 *
 * @package Quark\AuthorizationProviders
 */
class BasicAuth implements IQuarkAuthorizationProvider {
	/**
	 * @return string
	 */
	private static function _realm () {
		return sha1(Quark::Config()->WebHost());
	}

	/**
	 * @return string
	 */
	private static function _authentication () {
		return QuarkDTO::AUTHORIZATION_BASIC . ' realm="' . self::_realm() . '"';
	}

	/**
	 * @param string $name
	 * @param IQuarkAuthorizableModel $model
	 * @param QuarkDTO $input
	 *
	 * @return QuarkDTO
	 */
	public function Session ($name, IQuarkAuthorizableModel $model, QuarkDTO $input) {
		$authorization = $input->Authorization();

		if ($authorization == null)
			return QuarkDTO::ForHTTPAuthorizationPrompt(self::_authentication());

		$auth = explode(':', base64_decode($authorization->Value()));

		if (sizeof($auth) != 2)
			return QuarkDTO::ForHTTPAuthorizationPrompt(self::_authentication());

		$output = new QuarkDTO();
		$output->Data(new QuarkKeyValuePair($auth[0], $auth[1]));

		return $output;
	}

	/**
	 * @param string $name
	 * @param IQuarkAuthorizableModel $model
	 * @param $criteria
	 * @param $lifetime
	 *
	 * @return QuarkDTO
	 */
	public function Login ($name, IQuarkAuthorizableModel $model, $criteria, $lifetime) {
		// TODO: Implement Login() method.
	}

	/**
	 * @param string $name
	 * @param IQuarkAuthorizableModel $model
	 * @param QuarkKeyValuePair $id
	 *
	 * @return QuarkDTO
	 */
	public function Logout ($name, IQuarkAuthorizableModel $model, QuarkKeyValuePair $id) {
		return QuarkDTO::ForHTTPAuthorizationPrompt();
	}

	/**
	 * @param string $name
	 * @param IQuarkAuthorizableModel $model
	 * @param QuarkKeyValuePair $id
	 *
	 * @return bool
	 */
	public function SessionCommit ($name, IQuarkAuthorizableModel $model, QuarkKeyValuePair $id) {
		// TODO: Implement SessionCommit() method.
	}

	/**
	 * @param object $ini
	 *
	 * @return mixed
	 */
	public function SessionOptions ($ini) {
		// TODO: Implement SessionOptions() method.
	}
}