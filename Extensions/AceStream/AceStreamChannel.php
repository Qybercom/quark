<?php
namespace Quark\Extensions\AceStream;

use Quark\IQuarkModel;
use Quark\IQuarkStrongModel;

use Quark\QuarkDate;

/**
 * Class AceStreamChannel
 *
 * @property string $channel_id
 * @property string $name
 * @property string $infohash
 * @property int $status = self::STATUS_GREEN
 * @property int $bitrate
 * @property string[] $categories
 * @property float $availability
 * @property QuarkDate $availability_updated_at
 * @property bool $in_playlist
 * @property string $icon
 *
 * @package Quark\Extensions\AceStream
 */
class AceStreamChannel implements IQuarkModel, IQuarkStrongModel {
	const STATUS_GREEN = 2;
	const STATUS_YELLOW = 1;

	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'channel_id' => '',
			'name' => '',
			'infohash' => '',
			'status' => self::STATUS_GREEN,
			'bitrate' => 0,
			'categories' => array(),
			'availability' => 0.0,
			'availability_updated_at' => QuarkDate::FromTimestamp(),
			'in_playlist' => false,
			'icon' => ''
		);
	}

	/**
	 * @return mixed
	 */
	public function Rules () {
		// TODO: Implement Rules() method.
	}
}