<?php
namespace Quark\Extensions\FlowprintScript;

use Quark\IQuarkModel;
use Quark\IQuarkModelWithAfterPopulate;
use Quark\IQuarkModelWithDefaultExtract;
use Quark\IQuarkStrongModelWithRuntimeFields;

use Quark\QuarkModel;
use Quark\QuarkModelBehavior;

/**
 * Class FlowprintScriptNodePin
 *
 * @property string $id
 * @property string $kind
 * @property string $direction
 * @property string $place
 *
 * @property string $content
 *
 * @package Quark\Extensions\FlowprintScript
 */
class FlowprintScriptNodePin implements IQuarkModel, IQuarkStrongModelWithRuntimeFields, IQuarkModelWithDefaultExtract, IQuarkModelWithAfterPopulate {
	const KIND_FLOW = 'flow';
	const KIND_VALUE = 'value';
	const KIND_UNKNOWN = '';
	
	const DIRECTION_IN = 'in';
	const DIRECTION_OUT = 'out';
	const DIRECTION_UNKNOWN = '';
	
	const PLACE_HEADER = 'header';
	const PLACE_BODY = 'body';
	const PLACE_UNKNOWN = '';
	
	use QuarkModelBehavior;
	
	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'id' => $this->Nullable(''),
			'kind' => self::KIND_UNKNOWN,
			'direction' => self::DIRECTION_UNKNOWN,
			'place' => self::PLACE_UNKNOWN
		);
	}
	
	/**
	 * @return mixed
	 */
	public function Rules () {
		// TODO: Implement Rules() method.
	}
	
	/**
	 * @return mixed
	 */
	public function RuntimeFields () {
		return array(
			'content' => ''
		);
	}
	
	/**
	 * @param array $fields
	 * @param bool $weak
	 *
	 * @return array
	 */
	public function DefaultExtract ($fields, $weak) {
		return array(
			'id',
			'kind',
			'content'
		);
	}
	
	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function AfterPopulate ($raw) {
		if (isset($raw->direction) && is_object($raw->direction)) {
			$this->direction = self::DIRECTION_UNKNOWN;
			
			$in = isset($raw->direction->in) && $raw->direction->in == 'true';
			$out = isset($raw->direction->out) && $raw->direction->out == 'true';
			
			if ($in && !$out) $this->direction = self::DIRECTION_IN;
			if (!$in && $out) $this->direction = self::DIRECTION_OUT;
		}
	}
	
	/**
	 * @param string $kind = self::KIND_UNKNOWN
	 * @param string $direction = self::DIRECTION_UNKNOWN
	 * @param string $place = self::PLACE_UNKNOWN
	 * @param string $label = ''
	 * @param string $id = null
	 *
	 * @return FlowprintScriptNodePin|QuarkModel
	 */
	public static function Init ($kind = self::KIND_UNKNOWN, $direction = self::DIRECTION_UNKNOWN, $place = self::PLACE_UNKNOWN, $label = '', $id = null) {
		/**
		 * @var QuarkModel|FlowprintScriptNodePin $node
		 */
		$node = new QuarkModel(new FlowprintScriptNodePin(), array(
			'kind' => $kind,
			'direction' => $direction,
			'place' => $place,
			'content' => $label
		));
		
		if ($id !== null)
			$node->id = $id;
		
		return $node;
	}
	
	/**
	 * @param string $label = ''
	 * @param bool $header = true
	 * @param string $id = null
	 *
	 * @return QuarkModel|FlowprintScriptNodePin
	 */
	public static function FlowIn ($label = '', $header = true, $id = null) {
		return self::Init(
			self::KIND_FLOW,
			self::DIRECTION_IN,
			$header ? self::PLACE_HEADER : self::PLACE_BODY,
			$label, $id
		);
	}
	
	/**
	 * @param string $label = ''
	 * @param bool $header = true
	 * @param string $id = null
	 *
	 * @return QuarkModel|FlowprintScriptNodePin
	 */
	public static function FlowOut ($label = '', $header = true, $id = null) {
		return self::Init(
			self::KIND_FLOW,
			self::DIRECTION_OUT,
			$header ? self::PLACE_HEADER : self::PLACE_BODY,
			$label, $id
		);
	}
	
	/**
	 * @param string $label = ''
	 * @param bool $body = true
	 * @param string $id = null
	 *
	 * @return QuarkModel|FlowprintScriptNodePin
	 */
	public static function ValueIn ($label = '', $body = true, $id = null) {
		return self::Init(
			self::KIND_VALUE,
			self::DIRECTION_IN,
			$body ? self::PLACE_BODY : self::PLACE_HEADER,
			$label, $id
		);
	}
	
	/**
	 * @param string $label = ''
	 * @param bool $body = true
	 * @param string $id = null
	 *
	 * @return QuarkModel|FlowprintScriptNodePin
	 */
	public static function ValueOut ($label = '', $body = true, $id = null) {
		return self::Init(
			self::KIND_VALUE,
			self::DIRECTION_OUT,
			$body ? self::PLACE_BODY : self::PLACE_HEADER,
			$label, $id
		);
	}
}