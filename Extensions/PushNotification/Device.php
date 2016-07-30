<?php
namespace Quark\Extensions\PushNotification;

use Quark\IQuarkModel;

use Quark\IQuarkModelWithAfterFind;
use Quark\IQuarkModelWithAfterPopulate;
use Quark\QuarkDate;
use Quark\QuarkField;

/**
 * Class Device
 *
 * @package Quark\Extensions\PushNotification
 */
class Device implements IQuarkModel, IQuarkModelWithAfterPopulate {
	/**
	 * @var string $type = ''
	 */
	public $type = '';
	/**
	 * @var string $id = ''
	 */
	public $id = '';

	/**
	 * @var QuarkDate|string $date = ''
	 */
	public $date = '';

	/**
	 * @param string $type = ''
	 * @param string $id = ''
	 * @param QuarkDate|string $date = ''
	 */
	public function __construct ($type = '', $id = '', $date = '') {
		$this->type = (string)$type;
		$this->id = (string)$id;
		$this->date = QuarkDate::From($date);
	}

	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'id' => '',
			'type' => '',
			'date' => QuarkDate::GMTNow()
		);
	}

	/**
	 * @return mixed
	 */
	public function Rules () {
		return array(
			QuarkField::is($this->id, QuarkField::TYPE_STRING),
			QuarkField::is($this->type, QuarkField::TYPE_STRING)
		);
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function AfterPopulate ($raw) {
		$raw = (object)$raw;

		if (!isset($raw->date) || $raw->date == null)
			$this->date = null;
	}
}