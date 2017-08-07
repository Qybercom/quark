<?php
namespace Quark\Scenarios\Generate;

use Quark\IQuarkTask;

use Quark\Quark;
use Quark\QuarkCLIBehavior;

/**
 * Class ClusterKey
 *
 * @package Quark\Scenarios\Generate
 */
class ClusterKey implements IQuarkTask {
	use QuarkCLIBehavior;

	/**
	 * @param int $argc
	 * @param array $argv
	 *
	 * @return mixed
	 */
	public function Task ($argc, $argv) {
		$this->ShellView(
			'Generate/ClusterKey',
			'Your generated cluster key is: ' . $this->ShellLineSuccess(Quark::GuID())
		);
	}
}