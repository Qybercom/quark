<?php
namespace Quark\AuthorizationProviders;

use Quark\IQuarkAuthorizationProvider;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkModel;

/**
 * Class PHPDigestAuth
 *
 * @package Quark\AuthorizationProviders
 */
class PHPDigestAuth implements IQuarkAuthorizationProvider {
	/**
	 * @var array $_criteria
	 */
	private $_criteria;

	/**
	 * @param string $source
	 *
	 * @return array|null
	 */
	private function _digest ($source = '') {
		if (func_num_args() == 0)
			$source = $_SERVER['PHP_AUTH_DIGEST'];

		if (!preg_match_all('#(.*)=(.*)\,#Uis', $source . ',', $found, PREG_SET_ORDER)) return null;

		$data = array();

		foreach ($found as $item) {
			if (sizeof($item) != 3) continue;

			$data[trim($item[1])] = trim(str_replace('"', '', $item[2]));
		}

		$this->_criteria = $data;

		return array(
			'user' => $data['username'],
			'mask' => $data['response']
		);
	}

	/**
	 * @param $user
	 * @param $pass
	 *
	 * @return string
	 */
	public function Password ($user, $pass) {
		return md5($user . ':' . $_SERVER['SERVER_NAME'] . ':' . $pass);
	}

	/**
	 * http://php.net/manual/ru/features.http-auth.php
	 *
	 * @param       $pass
	 * @param array $data
	 *
	 * @return bool
	 */
	public function Verify ($pass, $data = []) {
		if (func_num_args() == 1)
			$data = $this->_criteria;

		$service = md5($_SERVER['REQUEST_METHOD'] . ':' . $data['uri']);
		$sign = $data['nonce'] . ':' . $data['nc'] . ':' . $data['cnonce'] . ':' . $data['qop'];

		return $data['response'] == md5($pass . ':' . $sign . ':' . $service);
	}

	/**
	 * @param string   $name
	 * @param QuarkDTO $request
	 * @param          $lifetime
	 *
	 * @return mixed
	 */
	public function Initialize ($name, QuarkDTO $request, $lifetime) {
		if (!isset($_SERVER['PHP_AUTH_DIGEST'])) {
			$response = new QuarkDTO();

			$response->Status(QuarkDTO::STATUS_401_UNAUTHORIZED);
			$response->Header(QuarkDTO::HEADER_WWW_AUTHENTICATE, 'Digest '
				. 'realm="' . $_SERVER['SERVER_NAME'] . '",'
				. 'qop="auth",'
				. 'nonce="' . Quark::GuID() . '",'
				. 'opaque="' . sha1(md5($_SERVER['SERVER_NAME']) . sha1($_SERVER['SERVER_NAME'])) . '",');

			return $response;
		}
		else return $this->_digest();
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