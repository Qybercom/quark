<?php
namespace Quark\AuthorizationProviders;

use Quark\IQuarkAuthorizableModel;
use Quark\IQuarkAuthorizationProvider;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkKeyValuePair;

/**
 * Class DigestAuth
 *
 * @package Quark\AuthorizationProviders
 */
class DigestAuth implements IQuarkAuthorizationProvider {
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
		$realm = self::_realm();

		return QuarkDTO::AUTHORIZATION_DIGEST . ' '
				. 'realm="' . $realm . '",'
				. 'qop="auth",'
				. 'nonce="' . Quark::GuID() . '",'
				. 'opaque="' . sha1(md5($realm) . sha1($realm)) . '"';
	}

	/**
	 * @param $user
	 * @param $pass
	 *
	 * @return string
	 */
	public static function Password ($user, $pass) {
		return md5($user . ':' . self::_realm() . ':' . $pass);
	}

	/**
	 * @param QuarkKeyValuePair|array $session = []
	 *
	 * @return string
	 */
	public static function UsernameOf ($session = []) {
		if ($session instanceof QuarkKeyValuePair)
			$session = self::Digest($session->Value());

		return isset($session['username']) ? $session['username'] : '';
	}

	/**
	 * @param QuarkKeyValuePair|array $session = []
	 *
	 * @return string
	 */
	public static function RealmOf ($session = []) {
		if ($session instanceof QuarkKeyValuePair)
			$session = self::Digest($session->Value());

		return isset($session['realm']) ? $session['realm'] : '';
	}

	/**
	 * @param string $source
	 *
	 * @return array|null
	 */
	public static function Digest ($source = '') {
		if (!preg_match_all('#(.*)=(.*)\,#Uis', $source . ',', $found, PREG_SET_ORDER)) return null;

		$data = array(
			'username' => '',
			'realm' => '',
			'nonce' => '',
			'uri' => '',
			'response' => '',
			'opaque' => '',
			'qop' => '',
			'nc' => '',
			'cnonce' => ''
		);

		foreach ($found as $item) {
			if (sizeof($item) != 3) continue;

			$data[trim($item[1])] = trim(str_replace('"', '', $item[2]));
		}

		return $data;
	}

	/**
	 * @param string $password
	 * @param QuarkKeyValuePair $session
	 *
	 * @return bool
	 */
	public static function Verify ($password, QuarkKeyValuePair $session) {
		$data = self::Digest($session->Value());

		$service = md5($session->Key() . ':' . $data['uri']);
		$sign = $data['nonce'] . ':' . $data['nc'] . ':' . $data['cnonce'] . ':' . $data['qop'];

		return $data['response'] == md5($password . ':' . $sign . ':' . $service);
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

		$output = new QuarkDTO();
		$output->Data(new QuarkKeyValuePair($input->Method(), $authorization->Value()));

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
}