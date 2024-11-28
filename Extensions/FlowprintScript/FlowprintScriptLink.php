<?php
namespace Quark\Extensions\FlowprintScript;

use Quark\IQuarkModel;
use Quark\IQuarkStrongModel;

use Quark\QuarkModelBehavior;

/**
 * Class FlowprintScriptLink
 *
 * @property string $p1
 * @property string $p2
 *
 * @package Quark\Extensions\FlowprintScript
 */
class FlowprintScriptLink implements IQuarkModel, IQuarkStrongModel {
	use QuarkModelBehavior;
	
	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'p1' => '',
			'p2' => '',
		);
	}
	
	/**
	 * @return mixed
	 */
	public function Rules () {
		// TODO: Implement Rules() method.
	}
}