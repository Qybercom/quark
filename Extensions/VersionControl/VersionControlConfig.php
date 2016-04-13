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
		// TODO: Implement Stacked() method.
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		// TODO: Implement ExtensionInstance() method.
	}
}