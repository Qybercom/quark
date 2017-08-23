<?php
namespace Quark\Extensions\Gravatar;

use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;

/**
 * Class Gravatar
 *
 * @package Quark\Extensions\Gravatar
 */
class Gravatar {
	/**
	 * @var string $_email = ''
	 */
	private $_email = '';

	/**
	 * @param string $email = ''
	 */
	public function __construct ($email = '') {
		$this->Email($email);
	}

	/**
	 * @param string $email = ''
	 *
	 * @return string
	 */
	public function Email ($email = '') {
		if (func_num_args() != 0)
			$this->_email = $email;

		return $this->_email;
	}

	/**
	 * @return string
	 */
	public function Hash () {
		return md5(strtolower(trim($this->_email)));
	}

	/**
	 * @param int $size = 50
	 * @param string $fallback = ''
	 *
	 * @return string
	 */
	public function Avatar ($size = 50 , $fallback = '') {
		return 'https://www.gravatar.com/avatar/' . $this->Hash()
			. '?s=' . $size
			. ($fallback ? '&d=' . urlencode($fallback) : '');
	}

	/**
	 * @return object
	 */
	public function Profile () {
		$profile = QuarkHTTPClient::To(
			'https://www.gravatar.com/' . $this->Hash() . '.json',
			QuarkDTO::ForGET(),
			new QuarkDTO(new QuarkJSONIOProcessor())
		);

		return isset($profile->entry[0]) ? $profile->entry[0] : null;
	}
}