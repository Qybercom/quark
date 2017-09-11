<?php
namespace Quark\Extensions\PubNub;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;

/**
 * Class PubNubConfig
 *
 * @package Quark\Extensions\PubNub
 */
class PubNubConfig implements IQuarkExtensionConfig {
	/**
	 * @var string $appKeyPub = ''
	 */
	public $appKeyPub = '';

	/**
	 * @var string $appKeySub = ''
	 */
	public $appKeySub = '';

	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

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
		if (isset($ini->AppKeyPub))
			$this->appKeyPub = $ini->AppKeyPub;

		if (isset($ini->AppKeySub))
			$this->appKeySub = $ini->AppKeySub;
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new PubNub($this->_name);
	}
}