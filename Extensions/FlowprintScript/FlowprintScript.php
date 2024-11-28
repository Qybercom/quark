<?php
namespace Quark\Extensions\FlowprintScript;

use Quark\IQuarkExtension;
use Quark\IQuarkLinkedModel;
use Quark\IQuarkModel;
use Quark\IQuarkModelWithAfterPopulate;
use Quark\IQuarkStrongModel;

use Quark\Quark;
use Quark\QuarkCollection;
use Quark\QuarkModel;
use Quark\QuarkModelBehavior;

/**
 * Class FlowprintScript
 *
 * @property QuarkCollection|FlowprintScriptNode[] $nodes
 * @property QuarkCollection|FlowprintScriptLink[] $links
 *
 * @package Quark\Extensions\FlowprintScript
 */
class FlowprintScript implements IQuarkExtension, IQuarkModel, IQuarkStrongModel, IQuarkLinkedModel, IQuarkModelWithAfterPopulate {
	use QuarkModelBehavior;
	
	/**
	 * @var FlowprintScriptConfig $_config;
	 */
	private $_config;
	
	/**
	 * @param string $config
	 */
	public function __construct ($config) {
		$this->_config = Quark::Config()->Extension($config);
	}
	
	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'nodes' => new QuarkCollection(new FlowprintScriptNode()),
			'links' => new QuarkCollection(new FlowprintScriptLink()),
		);
	}
	
	/**
	 * @return mixed
	 */
	public function Rules () {
		// TODO: Implement Rules() method.
	}
	
	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Link ($raw) {
		return new QuarkModel($this, $raw);
	}
	
	/**
	 * @return mixed
	 */
	public function Unlink () {
		return json_encode($this->Export());
	}
	
	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function AfterPopulate ($raw) {
		if ($this->_config->Compatible()) {
			if (isset($raw->blocks))
				$this->nodes->PopulateModelsWith($raw->blocks);
		}
	}
	
	/**
	 * @return array
	 */
	public function Data () {
		$blocks = array();
		foreach ($this->nodes as $node)
			$blocks[] = $node->Data();
		
		$links = array();
		foreach ($this->links as $link)
			$links[] = $link->Extract();
		
		return array(
			'blocks' => $blocks,
			'links' => $links,
		);
	}
	
	/**
	 * @return QuarkModel|FlowprintScript
	 */
	public function Init () {
		$processors = $this->_config->Processors();
		
		foreach ($this->nodes as $node) {
			foreach ($processors as $i => &$processor) {
				if (!($processor instanceof IQuarkFlowprintScriptProcessorNode)) continue;
				if ($processor->FlowprintScriptProcessorNodeKind() != $node->kind) continue;
				
				$processor->FlowprintScriptProcessorNodeInit($node);
			}
		}
		
		return $this->Container();
	}
	
	/**
	 * @param string $kind
	 * @param callable $init = null
	 *
	 * @return QuarkModel|FlowprintScriptNode
	 */
	public function InitNode ($kind, callable $init = null) {
		/**
		 * @var QuarkModel|FlowprintScriptNode $node
		 */
		$node = new QuarkModel(new FlowprintScriptNode());
		if ($init != null) $init($node);
		
		$processors = $this->_config->Processors();
		
		foreach ($processors as $i => &$processor) {
			if (!($processor instanceof IQuarkFlowprintScriptProcessorNode)) continue;
			if ($processor->FlowprintScriptProcessorNodeKind() != $kind) continue;
			
			$node->kind = $processor->FlowprintScriptProcessorNodeKind();
			
			$processor->FlowprintScriptProcessorNodeInit($node);
		}
		
		return $node;
	}
}