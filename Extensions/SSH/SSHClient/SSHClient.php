<?php
namespace Quark\Extensions\SSH\SSHClient;

use Quark\IQuarkExtension;

use Quark\Quark;

/**
 * Class SSHClient
 *
 * @package Quark\Extensions\SSH\SSHClient
 */
class SSHClient implements IQuarkExtension {
	/**
	 * @var SSHClientConfig $_config
	 */
	private $_config;

	/**
	 * @param string $config = ''
	 */
	public function __construct ($config = '') {
		$this->_config = Quark::Config()->Extension($config);
	}
}