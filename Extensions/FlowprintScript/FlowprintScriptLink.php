<?php
namespace Quark\Extensions\FlowprintScript;

use Quark\IQuarkModel;
use Quark\IQuarkStrongModel;

use Quark\QuarkModelBehavior;

/**
 * Class FlowprintScriptLink
 *
 * @property string $id
 * @property string $p1
 * @property string $p2
 * @property bool $enabled
 * @property string $comment
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
			'id' => $this->Nullable(''),
			'p1' => '',
			'p2' => '',
			'enabled' => true,
			'comment' => ''
		);
	}
	
	/**
	 * @return mixed
	 */
	public function Rules () {
		// TODO: Implement Rules() method.
	}
}