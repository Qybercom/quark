<?php
namespace Quark\Extensions\AceStream;

use Quark\IQuarkModel;
use Quark\IQuarkStrongModel;
use Quark\QuarkDate;

/**
 * Class AceStreamChannelGroupEPG
 *
 * @property string $name
 * @property QuarkDate $start
 * @property QuarkDate $stop
 *
 * @package Quark\Extensions\AceStream
 */
class AceStreamChannelGroupEPG implements IQuarkModel, IQuarkStrongModel {
	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'name' => '',
			'start' => QuarkDate::FromTimestamp(),
			'stop' => QuarkDate::FromTimestamp()
		);
	}

	/**
	 * @return mixed
	 */
	public function Rules () {
		// TODO: Implement Rules() method.
	}
}