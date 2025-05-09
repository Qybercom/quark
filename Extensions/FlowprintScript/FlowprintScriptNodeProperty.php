<?php
namespace Quark\Extensions\FlowprintScript;

use Quark\IQuarkModel;
use Quark\IQuarkStrongModel;

use Quark\QuarkModelBehavior;

/**
 * Class FlowprintScriptNodeProperty
 *
 * @property string $key
 * @property string $value
 * @property int $position
 * @property bool $editable
 *
 * @package Quark\Extensions\FlowprintScript
 */
class FlowprintScriptNodeProperty implements IQuarkModel, IQuarkStrongModel {
	use QuarkModelBehavior;
	
	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'key' => '',
			'value' => '',
			'position' => 0,
			'editable' => false
		);
	}
	
	/**
	 * @return mixed
	 */
	public function Rules () {
		// TODO: Implement Rules() method.
	}
}