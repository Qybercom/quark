<?php
namespace Quark\Extensions\FlowprintScript;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;

/**
 * Class FlowprintScriptConfig
 *
 * @package Quark\Extensions\FlowprintScript
 */
class FlowprintScriptConfig implements IQuarkExtensionConfig {
	/**
	 * @var string $_name
	 */
	private $_name;
	
	/**
	 * @var IQuarkFlowprintScriptProcessor[] $_processors = []
	 */
	private $_processors = array();
	
	/**
	 * @var bool $_compatible = true
	 */
	private $_compatible = true;
	
	/**
	 * @param bool $compatible = true
	 *
	 * @return bool
	 */
	public function Compatible ($compatible = true) {
		if (func_num_args() != 0)
			$this->_compatible = $compatible;
		
		return $this->_compatible;
	}
	
	/**
	 * @return IQuarkFlowprintScriptProcessor[]
	 */
	public function &Processors () {
		return $this->_processors;
	}
	
	/**
	 * @param IQuarkFlowprintScriptProcessor $processor
	 *
	 * @return FlowprintScriptConfig
	 */
	public function Processor (IQuarkFlowprintScriptProcessor $processor) {
		$this->_processors[] = $processor;
		
		return $this;
	}
	
	/**
	 * @param string $name
	 */
	public function Stacked ($name) {
		$this->_name = $name;
	}
	
	/**
	 * @return string
	 */
	public function ExtensionName () {
		return $this->_name;
	}
	
	/**
	 * @param object $ini
	 *
	 * @return mixed
	 */
	public function ExtensionOptions ($ini) {
		if (isset($ini->Compatible))
			$this->Compatible($ini->Compatible);
	}
	
	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new FlowprintScript($this->_name);
	}
}