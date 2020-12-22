<?php
namespace Quark\Extensions\AppleSignIn;

use Quark\Extensions\JOSE\IJOSEJWTIdentity;

/**
 * Class AppleID
 *
 * @package Quark\Extensions\AppleSignIn
 */
class AppleID implements IJOSEJWTIdentity {
	/**
	 * @var string $_email
	 */
	private $_email;

	/**
	 * @var string $_name
	 */
	private $_name;

	/**
	 * @param string $email = null
	 * @param string $name = null
	 */
	public function __construct ($email = null, $name = null) {
		$this->Email($email);
		$this->Name($name);
	}

	/**
	 * @param string $email = null
	 *
	 * @return string
	 */
	public function Email ($email = null) {
		if (func_num_args() != 0)
			$this->_email = $email;

		return $this->_email;
	}

	/**
	 * @param string $name = null
	 *
	 * @return string
	 */
	public function Name ($name = null) {
		if (func_num_args() != 0)
			$this->_name = $name;

		return $this->_name;
	}
}