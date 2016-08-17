<?php
namespace Quark\Extensions\VersionControl;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;

use Quark\QuarkKeyValuePair;

/**
 * Class VersionControlConfig
 *
 * @package Quark\Extensions\VersionControl
 */
class VersionControlConfig implements IQuarkExtensionConfig {
	/**
	 * @var IQuarkVersionControlProvider $_provider
	 */
	private $_provider;

	/**
	 * @var string $_repository
	 */
	private $_repository;

	/**
	 * @var QuarkKeyValuePair $_user
	 */
	private $_user;

	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @param IQuarkVersionControlProvider $provider
	 * @param string $repository
	 * @param string $username = ''
	 * @param string $password = ''
	 */
	public function __construct (IQuarkVersionControlProvider $provider, $repository, $username = '', $password = '') {
		$this->_provider = $provider;
		$this->_repository = $repository;
		$this->_user = new QuarkKeyValuePair($username, $password);
	}

	/**
	 * @return IQuarkVersionControlProvider
	 */
	public function &Provider () {
		return $this->_provider;
	}

	/**
	 * @return string
	 */
	public function &Repository () {
		return $this->_repository;
	}

	/**
	 * @return QuarkKeyValuePair
	 */
	public function &User () {
		return $this->_user;
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
		if (isset($ini->Username))
			$this->_user = new QuarkKeyValuePair($ini->Username, $this->_user->Value());

		if (isset($ini->Password))
			$this->_user = new QuarkKeyValuePair($this->_user->Key(), $ini->Password);

		if (isset($ini->Repository))
			$this->_repository = $ini->Repository;
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new VersionControl($this->_name);
	}
}