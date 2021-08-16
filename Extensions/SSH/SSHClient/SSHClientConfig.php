<?php
namespace Quark\Extensions\SSH\SSHClient;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;

/**
 * Class SSHClientConfig
 *
 * @package Quark\Extensions\SSH\SSHClient
 */
class SSHClientConfig implements IQuarkExtensionConfig {
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
		// TODO: Implement ExtensionOptions() method.
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new SSHClient($this->_name);
	}
}