<?php
namespace Quark\Extensions\PushNotification;

use Quark\IQuarkModel;

use Quark\QuarkField;

/**
 * Class Device
 *
 * @package Quark\Extensions\PushNotification
 */
class Device implements IQuarkModel {
	/**
	 * @var string $type = ''
	 */
	public $type = '';
	/**
	 * @var string $id = ''
	 */
	public $id = '';

	/**
	 * @param string $type
	 * @param string $id
	 */
	public function __construct ($type = '', $id = '') {
		$this->type = (string)$type;
		$this->id = (string)$id;
	}

	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'id' => '',
			'type' => ''
		);
	}

	/**
	 * @return mixed
	 */
	public function Rules () {
		return array(
			QuarkField::Type($this->id, QuarkField::TYPE_STRING),
			QuarkField::Type($this->type, QuarkField::TYPE_STRING)
		);
	}
}