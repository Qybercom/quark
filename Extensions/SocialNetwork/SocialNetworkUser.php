<?php
namespace Quark\Extensions\SocialNetwork;

use Quark\QuarkCultureISO;
use Quark\QuarkDate;
use Quark\QuarkFile;

/**
 * Class SocialNetworkUser
 *
 * @package Quark\Extensions\SocialNetwork
 */
class SocialNetworkUser {
	const GENDER_MALE = 'm';
	const GENDER_FEMALE = 'f';

	/**
	 * @var string $_id
	 */
	private $_id = '';

	/**
	 * @var string $_name
	 */
	private $_name = '';

	/**
	 * @var string $_accessToken
	 */
	private $_accessToken = '';

	/**
	 * @var string $_gender
	 */
	private $_gender = '';

	/**
	 * @var QuarkDate $_birthday
	 */
	private $_birthday;

	/**
	 * @var QuarkFile $_photo
	 */
	private $_photo;

	/**
	 * @var string $_page
	 */
	private $_page = '';

	/**
	 * @param string $id
	 * @param string $name
	 */
	public function __construct ($id = '', $name = '') {
		$this->_id = $id;
		$this->_name = $name;
	}

	/**
	 * @param string $id
	 *
	 * @return string
	 */
	public function ID ($id = '') {
		if (func_num_args() != 0)
			$this->_id = $id;

		return $this->_id;
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	public function Name ($name = '') {
		if (func_num_args() != 0)
			$this->_name = $name;

		return $this->_name;
	}

	/**
	 * @param string $token
	 *
	 * @return string
	 */
	public function AccessToken ($token = '') {
		if (func_num_args() != 0)
			$this->_accessToken = $token;

		return $this->_accessToken;
	}

	/**
	 * @param string $gender
	 *
	 * @return string
	 */
	public function Gender ($gender = '') {
		if (func_num_args() != 0)
			$this->_gender = $gender;

		return $this->_gender;
	}

	/**
	 * @param QuarkDate $birthday
	 *
	 * @return QuarkDate
	 */
	public function Birthday (QuarkDate $birthday = null) {
		if (func_num_args() != 0)
			$this->_birthday = $birthday;

		return $this->_birthday;
	}

	/**
	 * @param string $format
	 * @param string $birthday
	 *
	 * @return QuarkDate
	 */
	public function BirthdayByDate ($format = '', $birthday = '') {
		if (func_num_args() != 0) {
			$this->_birthday = QuarkDate::FromFormat($format, $birthday);
			$this->_birthday->Culture(new QuarkCultureISO());
		}

		return $this->_birthday;
	}

	/**
	 * @param QuarkFile $photo
	 *
	 * @return QuarkFile
	 */
	public function Photo (QuarkFile $photo = null) {
		if (func_num_args() != 0)
			$this->_photo = $photo;

		return $this->_photo;
	}

	/**
	 * @param string $link
	 *
	 * @return QuarkFile
	 */
	public function PhotoFromLink ($link = '') {
		if (func_num_args() != 0)
			$this->_photo = $link;

		return $this->_photo;
	}

	/**
	 * @param string $page
	 *
	 * @return string
	 */
	public function Page ($page = '') {
		if (func_num_args() != 0)
			$this->_page = $page;

		return $this->_page;
	}
}