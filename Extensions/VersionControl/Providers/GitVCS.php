<?php
namespace Quark\Extensions\VersionControl\Providers;

use Quark\QuarkKeyValuePair;

use Quark\Extensions\VersionControl\IQuarkVersionControlProvider;

/**
 * Class GitVCS
 *
 * @package Quark\Extensions\VersionControl\Providers
 */
class GitVCS implements IQuarkVersionControlProvider {
	/**
	 * @return bool
	 */
	public function VCSInit () {
		// TODO: Implement VCSInit() method.
	}

	/**
	 * @param string $url
	 * @param QuarkKeyValuePair $user
	 *
	 * @return bool
	 */
	public function VCSRepository ($url, QuarkKeyValuePair $user) {
		// TODO: Implement VCSRepository() method.
	}

	/**
	 * @param string $message
	 *
	 * @return bool
	 */
	public function VCSCommit ($message) {
		// TODO: Implement VCSCommit() method.
	}

	/**
	 * @return bool
	 */
	public function VCSPull () {
		// TODO: Implement VCSPull() method.
	}

	/**
	 * @return bool
	 */
	public function VCSPush () {
		// TODO: Implement VCSPush() method.
	}

	/**
	 * @param int $steps
	 *
	 * @return bool
	 */
	public function VCSRollback ($steps) {
		// TODO: Implement VCSRollback() method.
	}

	/**
	 * @param int $steps
	 *
	 * @return bool
	 */
	public function VCSRestore ($steps) {
		// TODO: Implement VCSRestore() method.
	}

	/**
	 * @return string
	 */
	public function VCSLastLog () {
		// TODO: Implement VCSLastLog() method.
	}
}