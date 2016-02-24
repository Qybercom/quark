<?php
namespace Quark\Extensions\VersionControl;

use Quark\IQuarkExtension;

use Quark\Quark;

/**
 * Class VersionControl
 *
 * @package Quark\Extensions\VersionControl
 */
class VersionControl implements IQuarkExtension {
	/**
	 * @var VersionControlConfig $_config
	 */
	private $_config;

	/**
	 * VersionControl constructor.
	 *
	 * @param string $config
	 */
	public function __construct ($config) {
		$this->_config = Quark::Config()->Extension($config);
	}

	/**
	 * @param string $message = 'upgrade'
	 *
	 * @return bool
	 */
	public function Commit ($message = 'upgrade') {
		return $this->_config->Provider()->VCSCommit($message);
	}

	/**
	 * @param string|bool $commit = 'update'
	 *
	 * @return bool
	 */
	public function Pull ($commit = 'update') {
		return $commit !== false
			? $this->_config->Provider()->VCSCommit($commit)
			: $this->_config->Provider()->VCSPull();
	}

	/**
	 * @param string|bool $commit = 'update'
	 *
	 * @return bool
	 */
	public function Push ($commit = 'update') {
		return $commit !== false
			? $this->_config->Provider()->VCSCommit($commit)
			: $this->_config->Provider()->VCSPush();
	}
}