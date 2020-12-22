<?php
namespace Quark\Extensions\GoogleFirebase;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;

/**
 * Class GoogleFirebaseConfig
 *
 * @package Quark\Extensions\GoogleFirebase
 */
class GoogleFirebaseConfig implements IQuarkExtensionConfig {
	/**
	 * @var string $_name
	 */
	private $_name;

	/**
	 * @var string $_appID = ''
	 */
	private $_appID = '';

	/**
	 * @var string $_appSecret = ''
	 */
	private $_appSecret = '';

	/**
	 * @var string $_projectID = ''
	 */
	private $_projectID = '';

	/**
	 * @var string $_senderID = ''
	 */
	private $_senderID = '';

	/**
	 * @var string $_measurementID = ''
	 */
	private $_measurementID = '';

	/**
	 * @var string $_authDomain = null
	 */
	private $_authDomain = null;

	/**
	 * @param string $appID = ''
	 *
	 * @return string
	 */
	public function AppID ($appID = '') {
		if (func_num_args() != 0)
			$this->_appID = $appID;

		return $this->_appID;
	}

	/**
	 * @param string $appSecret = ''
	 *
	 * @return string
	 */
	public function AppSecret ($appSecret = '') {
		if (func_num_args() != 0)
			$this->_appSecret = $appSecret;

		return $this->_appSecret;
	}

	/**
	 * @param string $projectID = ''
	 *
	 * @return string
	 */
	public function ProjectID ($projectID = '') {
		if (func_num_args() != 0)
			$this->_projectID = $projectID;

		return $this->_projectID;
	}

	/**
	 * @param string $senderID = ''
	 *
	 * @return string
	 */
	public function SenderID ($senderID = '') {
		if (func_num_args() != 0)
			$this->_senderID = $senderID;

		return $this->_senderID;
	}

	/**
	 * @param string $measurementID = ''
	 *
	 * @return string
	 */
	public function MeasurementID ($measurementID = '') {
		if (func_num_args() != 0)
			$this->_measurementID = $measurementID;

		return $this->_measurementID;
	}

	/**
	 * @return string
	 */
	public function AuthDomain () {
		return $this->_authDomain == null ? str_replace(GoogleFirebase::URL_TEMPLATE, $this->_projectID, GoogleFirebase::URL_TEMPLATE_AUTH_DOMAIN) : $this->_authDomain;
	}

	/**
	 * @return string
	 */
	public function Database () {
		return str_replace(GoogleFirebase::URL_TEMPLATE, $this->_projectID, GoogleFirebase::URL_TEMPLATE_DATABASE);
	}

	/**
	 * @return string
	 */
	public function StorageBucket () {
		return str_replace(GoogleFirebase::URL_TEMPLATE, $this->_projectID, GoogleFirebase::URL_TEMPLATE_STORAGE_BUCKET);
	}

	/**
	 * @return string
	 */
	public function JSON () {
		return json_encode(array(
			'apiKey' => $this->_appSecret,
			'authDomain' => $this->AuthDomain(),
			'databaseURL' => $this->Database(),
			'projectId' => $this->_projectID,
			'storageBucket' => $this->StorageBucket(),
			'messagingSenderId' => $this->_senderID,
			'appId' => $this->_appID,
			'measurementId' => $this->_measurementID
		));
	}

	/**
	 * @param string $name
	 */
	public function Stacked ($name) {
		$this->_name = $name;
	}

	/**
	 * @return string
	 */
	public function ExtensionName () {
		return $this->_name;
	}

	/**
	 * @param object $ini
	 *
	 * @return mixed
	 */
	public function ExtensionOptions ($ini) {
		if (isset($ini->AppID))
			$this->AppID($ini->AppID);

		if (isset($ini->AppID))
			$this->AppSecret($ini->AppSecret);

		if (isset($ini->AppID))
			$this->ProjectID($ini->ProjectID);

		if (isset($ini->SenderID))
			$this->SenderID($ini->SenderID);

		if (isset($ini->MeasurementID))
			$this->MeasurementID($ini->MeasurementID);

		if (isset($ini->AuthDomain))
			$this->_authDomain = $ini->AuthDomain;
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new GoogleFirebase($this->_name);
	}
}