<?php
namespace Quark\Extensions\UPnP\Providers\DLNA;

use Quark\IQuarkModel;
use Quark\IQuarkStrongModel;

use Quark\QuarkXMLNode;

/**
 * Class DLNAElementProperty
 *
 * @property string $name
 * @property string $value
 * @property array $attributes
 *
 * @package Quark\Extensions\UPnP\Providers\DLNA
 */
class DLNAElementProperty implements IQuarkModel, IQuarkStrongModel{
	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'name' => '',
			'value' => '',
			'attributes' => array()
		);
	}

	/**
	 * @return mixed
	 */
	public function Rules () {
		// TODO: Implement Rules() method.
	}

	/**
	 * @return QuarkXMLNode
	 */
	public function XMLNode () {
		return new QuarkXMLNode($this->name, $this->value, $this->attributes);
	}
}