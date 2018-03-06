<?php
namespace Quark\Extensions\AceStream;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;

/**
 * Class AceStreamConfig
 *
 * @package Quark\Extensions\AceStream
 */
class AceStreamConfig implements IQuarkExtensionConfig {
	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var string $_localURI = ''
	 */
	private $_localURI = '';

	/**
	 * @var bool $_cloudMode = true
	 */
	private $_cloudMode = true;

	/**
	 * @var string $_cloudKey = AceStream::CLOUD_TEST_KEY
	 */
	private $_cloudKey = AceStream::CLOUD_TEST_KEY;

	/**
	 * @var string $_cloudVersion = AceStream::CLOUD_VERSION
	 */
	private $_cloudVersion = AceStream::CLOUD_VERSION;

	/**
	 * @param string $uri = ''
	 *
	 * @return string
	 */
	public function LocalURI ($uri = '') {
		if (func_num_args() != 0)
			$this->_localURI = $uri;

		return $this->_localURI;
	}

	/**
	 * @param bool $mode = true
	 *
	 * @return bool
	 */
	public function CloudMode ($mode = true) {
		if (func_num_args() != 0)
			$this->_cloudMode = $mode;

		return $this->_cloudMode;
	}

	/**
	 * @param string $key = AceStream::CLOUD_TEST_KEY
	 *
	 * @return string
	 */
	public function CloudKey ($key = AceStream::CLOUD_TEST_KEY) {
		if (func_num_args() != 0)
			$this->_cloudKey = $key;

		return $this->_cloudKey;
	}

	/**
	 * @param string $version = AceStream::CLOUD_VERSION
	 *
	 * @return string
	 */
	public function CloudVersion ($version = AceStream::CLOUD_VERSION) {
		if (func_num_args() != 0)
			$this->_cloudVersion = $version;

		return $this->_cloudVersion;
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
		if (isset($ini->LocalURI))
			$this->_localURI = $ini->LocalURI;

		if (isset($ini->CloudMode))
			$this->_cloudMode = $ini->CloudMode;

		if (isset($ini->CloudKey))
			$this->_cloudKey = $ini->CloudKey;
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new AceStream($this->_name);
	}
}