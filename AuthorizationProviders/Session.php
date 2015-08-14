<?php
namespace Quark\AuthorizationProviders;

use Quark\IQuarkAuthorizationProvider;

use Quark\Quark;
use Quark\QuarkConfig;
use Quark\QuarkCookie;
use Quark\QuarkFile;
use Quark\QuarkKeyValuePair;
use Quark\QuarkModel;
use Quark\QuarkDTO;
use Quark\QuarkModelBehavior;
use Quark\QuarkObject;

/**
 * Class Session
 *
 * @package Quark\AuthorizationProviders
 */
class Session implements IQuarkAuthorizationProvider {
	const COOKIE_NAME = 'PHPSESSID';

	use QuarkModelBehavior;

	/**
	 * @var string $_sid
	 */
	private $_sid = '';

	/**
	 * @var string $_user
	 */
	private $_user = '';

	/**
	 * @var string $_signature
	 */
	private $_signature = '';

	/**
	 * @var int $_ttl = 0
	 */
	private $_ttl = 0;

	/**
	 * @var QuarkDTO $_output
	 */
	private $_output;

	/**
	 * @var string $_provider
	 */
	private $_provider;

	/**
	 * @var string $_cookie
	 */
	private $_cookie = self::COOKIE_NAME;

	/**
	 * @param string $provider
	 * @param string $cookie = self::COOKIE_NAME
	 */
	public function __construct ($provider = '', $cookie = self::COOKIE_NAME) {
		$this->_provider = $provider;
		$this->_cookie = $cookie;
	}

	/**
	 * @param string $name
	 * @param string $id
	 * @param int $lifetime (seconds)
	 *
	 * @return bool
	 */
	private function _end ($name, $id, $lifetime) {
		$this->_output = new QuarkDTO();
		$this->_output->Cookie(new QuarkCookie($this->_cookie, $id, $lifetime));
		$this->_output->AuthorizationProvider(new QuarkKeyValuePair($name, $id));

		return true;
	}

	/**
	 * @param string $name
	 * @param string $id
	 *
	 * @return QuarkFile
	 */
	private static function _storage ($name, $id) {
		return new QuarkFile(Quark::Host() . '/' . Quark::Config()->Location(QuarkConfig::RUNTIME) . '/Session/' . $name . '/' . $name . '-' . $id);
	}

	/**
	 * @param string $name
	 * @param QuarkCookie $session
	 *
	 * @return bool
	 */
	private function _session ($name, QuarkCookie $session = null) {
		if (!$session) return false;
		$session->value = trim($session->value);
		if (!$session->value) return false;

		$storage = self::_storage($name, $session->value);

		try {
			$json = json_decode($storage->Load()->Content());

			if (!$json || QuarkObject::isIterative($json)) return false;

			$this->_sid = $session->value;
			$this->_user = $json->user;
			$this->_signature = $json->signature;
			$this->_ttl = $json->ttl;

			return true;
		}
		catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * @param string $name
	 * @param QuarkModel $user
	 * @param QuarkDTO $input
	 *
	 * @return bool
	 */
	public function Recognize ($name, QuarkModel $user, QuarkDTO $input) {
		return $input->GetCookieByName($this->_cookie) != null;
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
		$session = $this->_session($name, $http
			? $input->GetCookieByName($this->_cookie)
			: ($input->AuthorizationProvider() != null ? $input->AuthorizationProvider()->ToCookie() : null));

		if (!$session) return false;

		$this->_output = new QuarkDTO();
		$this->_output->AuthorizationProvider(new QuarkKeyValuePair($name, $this->_sid));

		return $this->_user;
	}

	/**
	 * @param string $name
	 * @param QuarkModel $user
	 *
	 * @return QuarkDTO
	 */
	public function Output ($name, QuarkModel $user) {
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
		$sid = (bool)$this->_sid;
		$storage = self::_storage($name, $this->_sid);

		$this->_sid = Quark::GuID();
		$this->_user = $user->Export();
		$this->_signature = Quark::GuID();
		$this->_ttl = $lifetime;

		$old = $storage->Location();
		$storage->Location($storage->parent . '/' . $name . '-' . $this->_sid);
		$new = $storage->Location();

		if ($sid)
			rename($old, $new);

		$storage->Content(json_encode(array(
			'user' => $this->_user,
			'signature' => $this->_signature,
			'ttl' => $this->_ttl
		)));

		$storage->SaveContent();

		return $this->_end($name, $this->_sid, $lifetime);
	}

	/**
	 * @param string $name
	 * @param QuarkModel $user
	 *
	 * @return bool
	 */
	public function Logout ($name, QuarkModel $user) {
		$storage = self::_storage($name, $this->_sid);

		return ($this->_sid ? unlink($storage->Location()) : true)
			&& $this->_end($name, $this->_sid, -3600);
	}

	/**
	 * @param string $name
	 * @param QuarkModel $user
	 *
	 * @return string
	 */
	public function Signature ($name, QuarkModel $user) {
		return $this->_signature;
	}
}