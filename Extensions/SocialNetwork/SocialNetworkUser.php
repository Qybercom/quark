<?php
namespace Quark\Extensions\SocialNetwork;

use Quark\Quark;
use Quark\QuarkCultureISO;
use Quark\QuarkDate;
use Quark\QuarkDTO;
use Quark\QuarkFile;
use Quark\QuarkHTTPClient;
use Quark\QuarkModel;
use Quark\QuarkSession;

/**
 * Class SocialNetworkUser
 *
 * @package Quark\Extensions\SocialNetwork
 */
class SocialNetworkUser {
	const GENDER_MALE = 'm';
	const GENDER_FEMALE = 'f';
	const GENDER_UNKNOWN = 'u';

	/**
	 * @var string $_id
	 */
	private $_id = '';

	/**
	 * @var string $_name
	 */
	private $_name = '';

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
	 * @var string $_photoLink
	 */
	private $_photoLink;

	/**
	 * @var string $_page
	 */
	private $_page = '';

	/**
	 * @var string $_email
	 */
	private $_email = '';

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
	 * @param string $formatAlt
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
	 * @param bool $download = true
	 *
	 * @return QuarkFile
	 */
	public function PhotoFromLink ($link = '', $download = true) {
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
	 * @param string $page
	 *
	 * @return string
	 */
	public function Page ($page = '') {
		if (func_num_args() != 0)
			$this->_page = $page;

		return $this->_page;
	}

	/**
	 * @param string $email
	 *
	 * @return string
	 */
	public function Email ($email = '') {
		if (func_num_args() != 0)
			$this->_email = $email;

		return $this->_email;
	}
	
	/**
	 * @param SocialNetwork $network
	 * @param IQuarkSocialNetworkUserModel $model
	 * @param QuarkSession $session = null
	 *
	 * @return QuarkModel|IQuarkSocialNetworkUserModel
	 */
	public static function FromNetwork (SocialNetwork $network, IQuarkSocialNetworkUserModel $model, QuarkSession $session = null) {
		$profile = $network->Init();
		if ($profile == null) return null;

		$name = $model->SocialKey($network);
		if ($name === false) return null;
		
		/**
		 * @var QuarkModel|IQuarkSocialNetworkUserModel|IQuarkSocialNetworkAuthorizableUserModel $user
		 */
		$user = $network->User($model, $name);
		if ($user == null) return null;
		
		$login = $user->SocialLogin($network, $profile);
		if ($login === false) return null;
		
		if ($model instanceof IQuarkSocialNetworkAuthorizableUserModel) {
			if ($session == null) return null;
			
			$lifetime = $user->SocialLoginLifetime($network, $profile);
			if ($lifetime === false) return null;

			if (!($network->IsNewUser() ? $user->Create() : $user->Save())) return null;
	
			return $session->ForUser($user, null, $lifetime) ? $session->User() : null;
		}
		
		return $user;
	}
	
	/**
	 * @param string $config
	 * @param IQuarkSocialNetworkUserModel $model
	 * @param QuarkDTO $request
	 * @param QuarkSession $session = null
	 *
	 * @return QuarkModel|IQuarkSocialNetworkUserModel
	 */
	public static function FromRedirect ($config, IQuarkSocialNetworkUserModel $model, QuarkDTO $request, QuarkSession $session = null) {
		$social = new SocialNetwork($config);
		$social->SessionFromRedirect(Quark::WebLocation($request->URI()->Resource()), $request->code);
		
		return self::FromNetwork($social, $model, $session);
	}
	
	/**
	 * @param string $config
	 * @param IQuarkSocialNetworkUserModel $model
	 * @param string $token = ''
	 * @param QuarkSession $session = null
	 *
	 * @return QuarkModel|IQuarkSocialNetworkUserModel
	 */
	public static function FromToken ($config, IQuarkSocialNetworkUserModel $model, $token = '', QuarkSession $session = null) {
		$social = new SocialNetwork($config);
		$social->SessionFromToken($token);
		
		return self::FromNetwork($social, $model, $session);
	}
}