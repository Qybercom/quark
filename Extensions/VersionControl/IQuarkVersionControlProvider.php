<?php
namespace Quark\Extensions\VersionControl;

use Quark\QuarkKeyValuePair;

/**
 * Class IQuarkVersionControlProvider
 *
 * @package Quark\Extensions\VersionControl
 */
interface IQuarkVersionControlProvider {
	/**
	 * @return bool
	 */
	public function VCSInit();

	/**
	 * @param string $url
	 * @param QuarkKeyValuePair $user
	 *
	 * @return bool
	 */
	public function VCSRepository($url, QuarkKeyValuePair $user);

	/**
	 * @param string $message
	 *
	 * @return bool
	 */
	public function VCSCommit($message);

	/**
	 * @return bool
	 */
	public function VCSPull();

	/**
	 * @return bool
	 */
	public function VCSPush();

	/**
	 * @param int $steps
	 *
	 * @return bool
	 */
	public function VCSRollback($steps);

	/**
	 * @param int $steps
	 *
	 * @return bool
	 */
	public function VCSRestore($steps);

	/**
	 * @return string
	 */
	public function VCSLastLog();
}