<?php
namespace Quark\Extensions\FlowprintScript;

use Quark\QuarkModel;

/**
 * Interface IQuarkFlowprintScriptProcessorNode
 *
 * @package Quark\Extensions\FlowprintScript
 */
interface IQuarkFlowprintScriptProcessorNode extends IQuarkFlowprintScriptProcessor {
	/**
	 * @return string
	 */
	public function FlowprintScriptProcessorNodeKind();
	
	/**
	 * @param QuarkModel|FlowprintScriptNode &$node
	 *
	 * @return void
	 */
	public function FlowprintScriptProcessorNodeInit(QuarkModel &$node);
}