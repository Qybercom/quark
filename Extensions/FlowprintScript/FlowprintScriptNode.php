<?php
namespace Quark\Extensions\FlowprintScript;

use Quark\IQuarkModel;
use Quark\IQuarkStrongModelWithRuntimeFields;

use Quark\QuarkCollection;
use Quark\QuarkModel;
use Quark\QuarkModelBehavior;

/**
 * Class FlowprintScriptNode
 *
 * @property string $id
 * @property string $kind
 * @property float $x
 * @property float $y
 * @property QuarkCollection|FlowprintScriptNodePin[] $pins
 * @property QuarkCollection|FlowprintScriptNodeProperty[] $properties
 *
 * @property bool $header_use
 * @property string $header_content
 * @property bool $body_use
 * @property string $body_content
 * @property QuarkCollection|FlowprintScriptNodeProperty[] $properties_runtime
 *
 * @package Quark\Extensions\FlowprintScript
 */
class FlowprintScriptNode implements IQuarkModel, IQuarkStrongModelWithRuntimeFields {
	const KIND_UNKNOWN = '';
	
	use QuarkModelBehavior;
	
	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'id' => $this->Nullable(''),
			'kind' => self::KIND_UNKNOWN,
			'x' => 0.0,
			'y' => 0.0,
			'pins' => new QuarkCollection(new FlowprintScriptNodePin()),
			'properties' => new QuarkCollection(new FlowprintScriptNodeProperty())
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
			'header_use' => true,
			'header_content' => '',
			'body_use' => true,
			'body_content' => '',
			'properties_runtime' => new QuarkCollection(new FlowprintScriptNodeProperty())
		);
	}
	
	/**
	 * @param string $kind = FlowprintScriptNodePin::KIND_UNKNOWN
	 * @param string $direction = FlowprintScriptNodePin::DIRECTION_UNKNOWN
	 * @param string $place = FlowprintScriptNodePin::PLACE_UNKNOWN
	 * @param string $label = ''
	 * @param string $id = ''
	 * @param bool $unique = true
	 *
	 * @return bool
	 */
	public function Pin ($kind = FlowprintScriptNodePin::KIND_UNKNOWN, $direction = FlowprintScriptNodePin::DIRECTION_UNKNOWN, $place = FlowprintScriptNodePin::PLACE_UNKNOWN, $label = '', $id = '', $unique = true) {
		$_id = $this->id . '_' . $id;
		$found = false;
		
		if ($unique) {
			foreach ($this->pins as $pin) {
				if ($pin->id != $_id) continue;
				
				$found = true;
				
				$pin->PopulateWith(array(
					'kind' => $kind,
					'direction' => $direction,
					'place' => $place,
					'content' => $label
				));
			}
		}
		
		if (!$unique || !$found)
			$this->pins[] = FlowprintScriptNodePin::Init($kind, $direction, $place, $label, $_id);
		
		return true;
	}
	
	/**
	 * @param string $id = ''
	 * @param string $label = ''
	 * @param bool $header = true
	 * @param bool $unique = true
	 *
	 * @return bool
	 */
	public function PinFlowIn ($id = '', $label = '', $header = true, $unique = true) {
		return $this->Pin(
			FlowprintScriptNodePin::KIND_FLOW,
			FlowprintScriptNodePin::DIRECTION_IN,
			$header ? FlowprintScriptNodePin::PLACE_HEADER : FlowprintScriptNodePin::PLACE_BODY,
			$label, $id, $unique
		);
	}
	
	/**
	 * @param string $id = ''
	 * @param string $label = ''
	 * @param bool $header = true
	 * @param bool $unique = true
	 *
	 * @return bool
	 */
	public function PinFlowOut ($id = '', $label = '', $header = true, $unique = true) {
		return $this->Pin(
			FlowprintScriptNodePin::KIND_FLOW,
			FlowprintScriptNodePin::DIRECTION_OUT,
			$header ? FlowprintScriptNodePin::PLACE_HEADER : FlowprintScriptNodePin::PLACE_BODY,
			$label, $id, $unique
		);
	}
	
	/**
	 * @param string $id = ''
	 * @param string $label = ''
	 * @param bool $body = true
	 * @param bool $unique = true
	 *
	 * @return bool
	 */
	public function PinValueIn ($id = '', $label = '', $body = true, $unique = true) {
		return $this->Pin(
			FlowprintScriptNodePin::KIND_VALUE,
			FlowprintScriptNodePin::DIRECTION_IN,
			$body ? FlowprintScriptNodePin::PLACE_BODY : FlowprintScriptNodePin::PLACE_HEADER,
			$label, $id, $unique
		);
	}
	
	/**
	 * @param string $id = ''
	 * @param string $label = ''
	 * @param bool $body = true
	 * @param bool $unique = true
	 *
	 * @return bool
	 */
	public function PinValueOut ($id = '', $label = '', $body = true, $unique = true) {
		return $this->Pin(
			FlowprintScriptNodePin::KIND_VALUE,
			FlowprintScriptNodePin::DIRECTION_OUT,
			$body ? FlowprintScriptNodePin::PLACE_BODY : FlowprintScriptNodePin::PLACE_HEADER,
			$label, $id, $unique
		);
	}
	
	/**
	 * @param string $id = ''
	 * @param bool $idPrefix = true
	 *
	 * @return QuarkModel|FlowprintScriptNodePin
	 */
	public function PinById ($id = '', $idPrefix = true) {
		return $this->pins->SelectOne(array(
			'id' => ($idPrefix ? $this->id . '_' : '') . $id
		));
	}
	
	/**
	 * @param string $key = ''
	 * @param string $value = ''
	 * @param bool $runtime = false
	 * @param int $position = 0
	 *
	 * @return QuarkModel|FlowprintScriptNodeProperty
	 */
	public function Property ($key = '', $value = '', $runtime = false, $position = 0) {
		$query = array('key' => $key);
		$property = $this->PropertyValue($key);
		
		if ($property == null) {
			/**
			 * @var QuarkModel|FlowprintScriptNodeProperty $property
			 */
			$property = new QuarkModel(new FlowprintScriptNodeProperty(), array(
				'key' => $key,
				'position' => $position
			));
			
			if ($runtime) $this->properties_runtime[] = $property;
			else $this->properties[] = $property;
		}
		
		if ($runtime) $this->properties_runtime->Change($query, array('value' => $value));
		else $this->properties->Change($query, array('value' => $value));
		
		return $property;
	}
	
	/**
	 * @param string $key = ''
	 * @param bool $runtime = false
	 *
	 * @return QuarkModel|FlowprintScriptNodeProperty
	 */
	public function PropertyValue ($key = '', $runtime = false) {
		$query = array('key' => $key);
		
		return $runtime
			? $this->properties_runtime->SelectOne($query)
			: $this->properties->SelectOne($query);
	}
	
	/**
	 * @return array
	 */
	public function FlowprintData () {
		return array(
			'id' => $this->id,
			'class' => $this->kind,
			'x' => $this->x,
			'y' => $this->y,
			'properties' => $this->properties->Aggregate(array(QuarkModel::OPTION_SORT => array('position' => 1)))->Extract(),
			'kindOptions' => array(
				'header' => array(
					'use' => $this->header_use,
					'content' => $this->header_content,
					'pins' => array(
						'in' => $this->pins->Select(array(
							'place' => FlowprintScriptNodePin::PLACE_HEADER,
							'direction' => FlowprintScriptNodePin::DIRECTION_IN
						))->Extract(),
						'out' => $this->pins->Select(array(
							'place' => FlowprintScriptNodePin::PLACE_HEADER,
							'direction' => FlowprintScriptNodePin::DIRECTION_OUT
						))->Extract()
					)
				),
				'body' => array(
					'use' => $this->body_use,
					'content' => $this->body_content,
					'pins' => array(
						'in' => $this->pins->Select(array(
							'place' => FlowprintScriptNodePin::PLACE_BODY,
							'direction' => FlowprintScriptNodePin::DIRECTION_IN
						))->Extract(),
						'out' => $this->pins->Select(array(
							'place' => FlowprintScriptNodePin::PLACE_BODY,
							'direction' => FlowprintScriptNodePin::DIRECTION_OUT
						))->Extract()
					)
				)
			)
		);
	}
}