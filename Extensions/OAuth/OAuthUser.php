<?php
namespace Quark\Extensions\OAuth;

use Quark\QuarkFile;
use Quark\QuarkHTTPClient;

/**
 * Class OAuthUser
 *
 * @package Quark\Extensions\OAuth
 */
class OAuthUser {
	/**
	 * @var string $_id = ''
	 */
	private $_id = '';

	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var string $_email = ''
	 */
	private $_email = '';

	/**
	 * @var QuarkFile $_avatar = null
	 */
	private $_avatar = null;

	/**
	 * @var string $_avatarLink = ''
	 */
	private $_avatarLink = '';

	/**
	 * @param string $id = ''
	 * @param string $name = ''
	 */
	public function __construct ($id = '', $name = '') {
		$this->ID($id);
		$this->Name($name);
	}

	/**
	 * @param string $id = ''
	 *
	 * @return string
	 */
	public function ID ($id = '') {
		if (func_num_args() != 0)
			$this->_id = $id;

		return $this->_id;
	}

	/**
	 * @param string $name = ''
	 *
	 * @return string
	 */
	public function Name ($name = '') {
		if (func_num_args() != 0)
			$this->_name = $name;

		return $this->_name;
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
	 * @param QuarkFile $avatar = null
	 *
	 * @return QuarkFile
	 */
	public function Avatar (QuarkFile $avatar = null) {
		if (func_num_args() != 0)
			$this->_avatar = $avatar;

		return $this->_avatar;
	}

	/**
	 * @param string $link = ''
	 *
	 * @return QuarkFile
	 */
	public function AvatarFromLink ($link = '') {
		if (func_num_args() != 0) {
			$this->_avatarLink = $link;
			$this->_avatar = QuarkHTTPClient::Download($link);
		}

		return $this->_avatar;
	}

	/**
	 * @return string
	 */
	public function AvatarLink () {
		return $this->_avatarLink;
	}
}