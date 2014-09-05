<?php
namespace Quark\Extensions\PushNotification;

/**
 * Class Device
 *
 * @package Quark\Extensions\PushNotification
 */
class Device {
	/**
	 * @var string
	 */
	public $type = '';
	/**
	 * @var string
	 */
	public $id = '';

	/**
	 * @param string $type
	 * @param string $id
	 */
	public function __construct ($type = '', $id = '') {
		$this->type = $type;
		$this->id = $id;
	}
} 