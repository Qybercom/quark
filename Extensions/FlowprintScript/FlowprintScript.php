<?php
namespace Quark\Extensions\FlowprintScript;

use Quark\IQuarkExtension;
use Quark\IQuarkLinkedModel;
use Quark\IQuarkModel;
use Quark\IQuarkModelWithAfterPopulate;
use Quark\IQuarkStrongModel;

use Quark\Quark;
use Quark\QuarkCollection;
use Quark\QuarkKeyValuePair;
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
	 * @var QuarkKeyValuePair[][] $_links = []
	 */
	private $_links = array();
	
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
		return new QuarkModel($this, is_object($raw) ? $raw : $this);
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
	 * @return QuarkModel|FlowprintScript
	 */
	public function BuildLinks () {
		foreach ($this->links as $link) {
			/**
			 * @var QuarkModel|FlowprintScriptNode $node1
			 */
			$node1 = $this->nodes->SelectOne(array(
				'pins.id' => $link->p2
			));
			
			if (!isset($this->_links[$link->p1]))
				$this->_links[$link->p1] = array();
			
			if ($node1 != null)
				$this->_links[$link->p1][] = new QuarkKeyValuePair($node1->id, $link->p2);
			
			/**
			 * @var QuarkModel|FlowprintScriptNode $node2
			 */
			$node2 = $this->nodes->SelectOne(array(
				'pins.id' => $link->p1
			));
			
			if (!isset($this->_links[$link->p2]))
				$this->_links[$link->p2] = array();
			
			if ($node2 != null)
				$this->_links[$link->p2][] = new QuarkKeyValuePair($node2->id, $link->p1);
		}
		
		unset($link, $node1, $node2);
		
		return $this->Container();
	}
	
	/**
	 * @return array
	 */
	public function FlowprintData () {
		$blocks = array();
		foreach ($this->nodes as $node)
			$blocks[] = $node->FlowprintData();
		
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
		foreach ($this->nodes as $node)
			$this->_processorNode($node);
		
		return $this->Container();
	}
	
	/**
	 * @param callable $init
	 *
	 * @return QuarkModel|FlowprintScriptNode
	 */
	public function InitNode (callable $init) {
		/**
		 * @var QuarkModel|FlowprintScriptNode $node
		 */
		$node = new QuarkModel(new FlowprintScriptNode());
		
		$init($node);
		$this->_processorNode($node);
		
		unset($init);
		
		return $node;
	}
	
	/**
	 * @param QuarkModel|FlowprintScriptNode &$node
	 *
	 * @return void
	 */
	private function _processorNode (QuarkModel &$node) {
		/**
		 * @var QuarkModel|FlowprintScript $script
		 */
		$script = $this->Container();
		$processors = $this->_config->Processors();
		
		foreach ($processors as $i => &$processor) {
			if (!($processor instanceof IQuarkFlowprintScriptProcessorNode)) continue;
			if (!$processor->FlowprintScriptProcessorNodeApplicable($script, $node)) continue;
			
			$processor->FlowprintScriptProcessorNodeInit($script, $node);
		}
		
		unset($i, $processor, $processors);
	}
	
	/**
	 * @param string $pinID = ''
	 *
	 * @return QuarkKeyValuePair[]
	 */
	public function Links ($pinID = '') {
		return isset($this->_links[$pinID]) ? $this->_links[$pinID] : array();
	}
	
	/**
	 * @param string $nodeID = ''
	 * @param string $pinID = ''
	 * @param bool $pinIDPrefix = true
	 *
	 * @return QuarkKeyValuePair[]
	 */
	public function NodeLinks ($nodeID = '', $pinID = '', $pinIDPrefix = true) {
		/**
		 * @var QuarkModel|FlowprintScriptNode $node
		 */
		$node = $this->nodes->SelectOne(array('id' => $nodeID));
		if ($node == null) return array();
		
		$all = func_num_args() < 2;
		$pIDs = array(($pinIDPrefix ? $nodeID . '_' : '') . $pinID);
		$out = array();
		
		if ($all) {
			foreach ($node->pins as $pin)
				$pIDs[] = $pin->id;
			
			unset($pin);
		}
		
		foreach ($this->_links as $pin => &$links) {
			if (!in_array($pin, $pIDs)) continue;
			
			foreach ($links as $i => &$link)
				$out[$pin] = $link;
		}
		
		unset($i, $link, $links, $pIDs, $all);
		
		return $out;
	}
	
	/**
	 * @param string $nodeID = ''
	 * @param string $pinID = ''
	 * @param bool $pinIDPrefix = true
	 *
	 * @return QuarkKeyValuePair
	 */
	public function NodeLink ($nodeID = '', $pinID = '', $pinIDPrefix = true) {
		$links = $this->NodeLinks($nodeID, $pinID, $pinIDPrefix);
		$pID = ($pinIDPrefix ? $nodeID . '_' : '') . $pinID;
		
		return isset($links[$pID]) ? $links[$pID] : null;
	}
	
	/**
	 * @param string $id = ''
	 *
	 * @return QuarkModel|FlowprintScriptNode
	 */
	public function Node ($id = '') {
		return $this->nodes->SelectOne(array(
			'id' => $id
		));
	}
	
	/**
	 * @param QuarkKeyValuePair[] $links = []
	 *
	 * @return QuarkCollection|FlowprintScriptNode[]
	 */
	public function NodesByLinks ($links = []) {
		$ids = array();
		
		foreach ($links as $i => &$link)
			if ($link instanceof QuarkKeyValuePair)
				$ids[] = $link->Key();
		
		unset($i, $link, $links);
		
		return $this->nodes->Select(array(
			'id' => array('$in' => $ids)
		));
	}
	
	/**
	 * @param string $nodeID = ''
	 * @param string $pinID = ''
	 * @param bool $pinIDPrefix = true
	 *
	 * @return mixed
	 */
	public function Data ($nodeID = '', $pinID = '', $pinIDPrefix = true) {
		/**
		 * @var QuarkModel|FlowprintScriptNode $node
		 */
		$node = $this->nodes->SelectOne(array('id' => $nodeID));
		if ($node == null) return null;
		
		/**
		 * @var QuarkModel|FlowprintScript $script
		 */
		$script = $this->Container();
		$processors = $this->_config->Processors();
		$pID = ($pinIDPrefix ? $nodeID . '_' : '') . $pinID;
		$out = null;
		
		foreach ($processors as $i => &$processor) {
			if (!($processor instanceof IQuarkFlowprintScriptProcessorNode)) continue;
			if (!$processor->FlowprintScriptProcessorNodeApplicable($script, $node)) continue;
			
			$out = $processor->FlowprintScriptProcessorNodeData($script, $node, $pID);
			break;
		}
		
		unset($i, $processor, $processors);
		
		return $out;
	}
	
	/**
	 * @param QuarkModel|FlowprintScriptNode &$node
	 *
	 * @return void
	 */
	public function Process (QuarkModel $node) {
		/**
		 * @var QuarkModel|FlowprintScript $script
		 */
		$script = $this->Container();
		$processors = $this->_config->Processors();
		$next = null;
		
		foreach ($processors as $i => &$processor) {
			if (!($processor instanceof IQuarkFlowprintScriptProcessorNode)) continue;
			if (!$processor->FlowprintScriptProcessorNodeApplicable($script, $node)) continue;
			
			$next = $processor->FlowprintScriptProcessorNodeProcess($script, $node);
		
			if ($next instanceof QuarkCollection)
				foreach ($next as $n)
					$this->Process($n);
		}
		
		unset($i, $processor, $processors, $next, $script);
	}
}