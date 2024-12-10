<?php
namespace Quark\Extensions\FlowprintScript;

use Quark\QuarkCollection;
use Quark\QuarkModel;

/**
 * Interface IQuarkFlowprintScriptProcessorNode
 *
 * @package Quark\Extensions\FlowprintScript
 */
interface IQuarkFlowprintScriptProcessorNode extends IQuarkFlowprintScriptProcessor {
	/**
	 * @param QuarkModel|FlowprintScript &$script
	 * @param QuarkModel|FlowprintScriptNode &$node
	 *
	 * @return bool
	 */
	public function FlowprintScriptProcessorNodeApplicable(QuarkModel &$script, QuarkModel &$node);
	
	/**
	 * @param QuarkModel|FlowprintScript &$script
	 * @param QuarkModel|FlowprintScriptNode &$node
	 *
	 * @return void
	 */
	public function FlowprintScriptProcessorNodeInit(QuarkModel &$script, QuarkModel &$node);
	
	/**
	 * @param QuarkModel|FlowprintScript &$script
	 * @param QuarkModel|FlowprintScriptNode &$node
	 *
	 * @return QuarkCollection|FlowprintScriptNode[]
	 */
	public function FlowprintScriptProcessorNodeProcess(QuarkModel &$script, QuarkModel &$node);
	
	/**
	 * @param QuarkModel|FlowprintScript &$script
	 * @param QuarkModel|FlowprintScriptNode &$node
	 * @param string $pinID
	 *
	 * @return mixed
	 */
	public function FlowprintScriptProcessorNodeData(QuarkModel &$script, QuarkModel &$node, $pinID);
}