<?php
namespace Quark\Extensions\SocialNetwork;

use Quark\QuarkCultureISO;
use Quark\QuarkDate;
use Quark\QuarkFile;
use Quark\QuarkHTTPClient;
use Quark\QuarkLanguage;

/**
 * Class SocialNetworkUser
 *
 * @package Quark\Extensions\SocialNetwork
 */
class SocialNetworkUser {
	const GENDER_MALE = 'm';
	const GENDER_FEMALE = 'f';
	const GENDER_UNKNOWN = '';

	/**
	 * @var object $_raw = null
	 */
	private $_raw = null;

	/**
	 * @var string $_id = ''
	 */
	private $_id = '';

	/**
	 * @var string $_username = ''
	 */
	private $_username = '';

	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var string $_gender = self::GENDER_UNKNOWN
	 */
	private $_gender = self::GENDER_UNKNOWN;

	/**
	 * @var QuarkDate $_birthday
	 */
	private $_birthday;

	/**
	 * @var QuarkFile $_photo
	 */
	private $_photo;

	/**
	 * @var string $_photoLink = ''
	 */
	private $_photoLink = '';

	/**
	 * @var string $_page = ''
	 */
	private $_page = '';

	/**
	 * @var string $_email = ''
	 */
	private $_email = '';

	/**
	 * @var QuarkDate $_registeredAt
	 */
	private $_registeredAt;

	/**
	 * @var bool $_verified = false
	 */
	private $_verified = false;

	/**
	 * @var string $_location = ''
	 */
	private $_location = '';

	/**
	 * @var string $_language = QuarkLanguage::ANY
	 */
	private $_language = QuarkLanguage::ANY;

	/**
	 * @var string $_bio = ''
	 */
	private $_bio = '';

	/**
	 * @var string $_company = ''
	 */
	private $_company = '';

	/**
	 * @param string $id = ''
	 * @param string $name = ''
	 * @param object $raw = null
	 */
	public function __construct ($id = '', $name = '', $raw = null) {
		$this->_id = $id;
		$this->_name = $name;
		$this->_raw = $raw;
	}

	/**
	 * @param object $raw = null
	 *
	 * @return object
	 */
	public function Raw ($raw = null) {
		if (func_num_args() != 0)
			$this->_raw = $raw;

		return $this->_raw;
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
	 * @param string $username = ''
	 *
	 * @return string
	 */
	public function Username ($username = '') {
		if (func_num_args() != 0)
			$this->_username = $username;

		return $this->_username;
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
	 * @param string $gender = self::GENDER_UNKNOWN
	 *
	 * @return string
	 */
	public function Gender ($gender = self::GENDER_UNKNOWN) {
		if (func_num_args() != 0)
			$this->_gender = $gender;

		return $this->_gender;
	}

	/**
	 * @param QuarkDate $birthday = null
	 *
	 * @return QuarkDate
	 */
	public function Birthday (QuarkDate $birthday = null) {
		if (func_num_args() != 0)
			$this->_birthday = $birthday;

		return $this->_birthday;
	}

	/**
	 * @param string $format = ''
	 * @param string $birthday = ''
	 * @param string $formatAlt = ''
	 *
	 * @return QuarkDate
	 */
	public function BirthdayByDate ($format = '', $birthday = '', $formatAlt = '') {
		if (func_num_args() != 0) {
			if (func_num_args() < 3)
				$formatAlt = $format;

			try {
				$this->_birthday = QuarkDate::FromFormat($format, $birthday);
			}
			catch (\Exception $e) {
				$this->_birthday = QuarkDate::FromFormat($formatAlt, $birthday);
			}

			$this->_birthday->Culture(new QuarkCultureISO());
		}

		return $this->_birthday;
	}

	/**
	 * @param QuarkFile $photo = null
	 *
	 * @return QuarkFile
	 */
	public function Photo (QuarkFile $photo = null) {
		if (func_num_args() != 0)
			$this->_photo = $photo;

		return $this->_photo;
	}

	/**
	 * @param string $link = ''
	 * @param bool $download = false
	 *
	 * @return QuarkFile
	 */
	public function PhotoFromLink ($link = '', $download = false) {
		if (func_num_args() != 0) {
			$this->_photoLink = $link;

			if ($download)
				$this->_photo = QuarkHTTPClient::Download($link);
		}

		return $this->_photo;
	}

	/**
	 * @return string
	 */
	public function PhotoLink () {
		return $this->_photoLink;
	}

	/**
	 * @param string $page = ''
	 *
	 * @return string
	 */
	public function Page ($page = '') {
		if (func_num_args() != 0)
			$this->_page = $page;

		return $this->_page;
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
	 * @param QuarkDate $date = null
	 *
	 * @return QuarkDate
	 */
	public function RegisteredAt (QuarkDate $date = null) {
		if (func_num_args() != 0)
			$this->_registeredAt = $date;

		return $this->_registeredAt;
	}

	/**
	 * @param bool $verified = false
	 *
	 * @return bool
	 */
	public function Verified ($verified = false) {
		if (func_num_args() != 0)
			$this->_verified = $verified;

		return $this->_verified;
	}

	/**
	 * @param string $location = ''
	 *
	 * @return string
	 */
	public function Location ($location = '') {
		if (func_num_args() != 0)
			$this->_location = $location;

		return $this->_location;
	}

	/**
	 * @param string $language = QuarkLanguage::ANY
	 *
	 * @return string
	 */
	public function Language ($language = QuarkLanguage::ANY) {
		if (func_num_args() != 0)
			$this->_language = $language;

		return $this->_language;
	}

	/**
	 * @param string $bio = ''
	 *
	 * @return string
	 */
	public function Bio ($bio = '') {
		if (func_num_args() != 0)
			$this->_bio = $bio;

		return $this->_bio;
	}

	/**
	 * @param string $company = ''
	 *
	 * @return string
	 */
	public function Company ($company = '') {
		if (func_num_args() != 0)
			$this->_company = $company;

		return $this->_company;
	}
}