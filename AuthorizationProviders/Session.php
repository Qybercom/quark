<?php
namespace Quark\AuthorizationProviders;

use Quark\IQuarkAuthorizableModel;
use Quark\IQuarkAuthorizationProvider;

use Quark\Quark;
use Quark\QuarkConfig;
use Quark\QuarkCookie;
use Quark\QuarkFile;
use Quark\QuarkKeyValuePair;
use Quark\QuarkDTO;
use Quark\QuarkModel;
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
	 * @var QuarkModel $_user
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
	 * @var string $_name
	 */
	private $_name = '';

	/**
	 * @param string $provider
	 * @param string $cookie = self::COOKIE_NAME
	 */
	public function __construct ($provider = '', $cookie = self::COOKIE_NAME) {
		$this->_provider = $provider;
		$this->_cookie = $cookie;
	}

	/**
	 * @param int $lifetime (seconds)
	 *
	 * @return bool
	 */
	private function _end ($lifetime) {
		$this->_output = new QuarkDTO();
		$this->_output->Cookie(new QuarkCookie($this->_cookie, $this->_sid, $lifetime));
		$this->_output->AuthorizationProvider(new QuarkKeyValuePair($this->_name, $this->_sid));

		return true;
	}

	/**
	 * @param string $name
	 * @param string $id
	 *
	 * @return QuarkFile
	 */
	private static function _storage ($name, $id) {
		return new QuarkFile(Quark::Host() . '/' . Quark::Config()->Location(QuarkConfig::RUNTIME) . '/Session/' . $name . '/' . $name . '-' . $id . '.sid');
	}

	/**
	 * @param string $name
	 * @param IQuarkAuthorizableModel $user
	 * @param QuarkDTO $input
	 *
	 * @return bool
	 */
	public function Recognize ($name, IQuarkAuthorizableModel $user, QuarkDTO $input) {
		return $input->GetCookieByName($this->_cookie) != null;
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
		$this->_name = $name;
		$session = $http
			? $input->GetCookieByName($this->_cookie)
			: ($input->AuthorizationProvider() != null ? $input->AuthorizationProvider()->ToCookie() : null);

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

			$this->_output = new QuarkDTO();
			$this->_output->AuthorizationProvider(new QuarkKeyValuePair($this->_name, $this->_sid));

			return true;
		}
		catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * @return QuarkDTO
	 */
	public function Output () {
		return $this->_output;
	}

	/**
	 * @param $criteria
	 * @param int $lifetime (seconds)
	 *
	 * @return bool
	 */
	public function Login ($criteria, $lifetime) {
		$sid = (bool)$this->_sid;
		$storage = self::_storage($this->_name, $this->_sid);

		$this->_sid = Quark::GuID();
		$this->_signature = Quark::GuID();
		$this->_ttl = $lifetime;

		$old = $storage->Location();
		$storage->Location($storage->parent . '/' . $this->_name . '-' . $this->_sid . '.sid');
		$new = $storage->Location();

		if ($sid)
			rename($old, $new);

		$storage->Content(json_encode(array(
			'user' => $this->_user->Extract(),
			'signature' => $this->_signature,
			'ttl' => $this->_ttl
		)));

		$storage->SaveContent();

		return $this->_end($lifetime);
	}

	/**
	 * @param QuarkModel $user
	 *
	 * @return QuarkModel
	 */
	public function User (QuarkModel $user = null) {
		if (func_num_args() != 0)
			$this->_user = $user;

		return $this->_user;
	}

	/**
	 * @return bool
	 */
	public function Logout () {
		$storage = self::_storage($this->_name, $this->_sid);

		return ($this->_sid ? unlink($storage->Location()) : true)
			&& $this->_end(-3600);
	}

	/**
	 * @return string
	 */
	public function Signature () {
		return $this->_signature;
	}
}