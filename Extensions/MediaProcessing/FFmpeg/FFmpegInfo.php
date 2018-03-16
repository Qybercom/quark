<?php
namespace Quark\Extensions\MediaProcessing\FFmpeg;

use Quark\QuarkDate;
use Quark\QuarkDateInterval;
use Quark\QuarkObject;

/**
 * Class FFmpegInfo
 *
 * @package Quark\Extensions\MediaProcessing\FFmpeg
 */
class FFmpegInfo {
	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var int $_size = 0
	 */
	private $_size = 0;

	/**
	 * @var string $_formatName = ''
	 */
	private $_formatName = '';

	/**
	 * @var string $_formatNameLong = ''
	 */
	private $_formatNameLong = '';

	/**
	 * @var int $_bitRate = 0
	 */
	private $_bitRate = 0;

	/**
	 * @var int $_start = 0
	 */
	private $_start = 0;

	/**
	 * @var QuarkDateInterval $_duration
	 */
	private $_duration;

	/**
	 * @var string $_tagTitle = ''
	 */
	private $_tagTitle = '';

	/**
	 * @var string $_tagEncoder = ''
	 */
	private $_tagEncoder = '';

	/**
	 * @var QuarkDate $_tagCreated
	 */
	private $_tagCreated;

	/**
	 * @var FFmpegStream[] $_streams = []
	 */
	private $_streams = array();

	/**
	 * @param string $name = ''
	 * @param int $size = 0
	 */
	public function __construct ($name = '', $size = 0) {
		$this->Name($name);
		$this->Size($size);
	}

	/**
	 * @param string $name = ''
	 *
	 * @return string
	 */
	public function Name ($name = '') {
		if (func_num_args() != 0)
			$this->_name = $name;

		return $this->_name;
	}

	/**
	 * @param int $size = 0
	 *
	 * @return int
	 */
	public function Size ($size = 0) {
		if (func_num_args() != 0)
			$this->_size = $size;

		return $this->_size;
	}

	/**
	 * @param string $name = ''
	 *
	 * @return string
	 */
	public function FormatName ($name = '') {
		if (func_num_args() != 0)
			$this->_formatName = $name;

		return $this->_formatName;
	}

	/**
	 * @param string $name = ''
	 *
	 * @return string
	 */
	public function FormatNameLong ($name = '') {
		if (func_num_args() != 0)
			$this->_formatNameLong = $name;

		return $this->_formatNameLong;
	}

	/**
	 * @param string $rate = '0/0'
	 *
	 * @return string
	 */
	public function BitRate ($rate = '0/0') {
		if (func_num_args() != 0)
			$this->_bitRate = $rate;

		return $this->_bitRate;
	}

	/**
	 * @param int $start = 0
	 *
	 * @return int
	 */
	public function Start ($start = 0) {
		if (func_num_args() != 0)
			$this->_start = $start;

		return $this->_start;
	}

	/**
	 * @param QuarkDateInterval $duration = null
	 *
	 * @return QuarkDateInterval
	 */
	public function Duration (QuarkDateInterval $duration = null) {
		if (func_num_args() != 0)
			$this->_duration = $duration;

		return $this->_duration;
	}

	/**
	 * @param string $title = ''
	 *
	 * @return string
	 */
	public function TagTitle ($title = '') {
		if (func_num_args() != 0)
			$this->_tagTitle = $title;

		return $this->_tagTitle;
	}

	/**
	 * @param string $encoder = ''
	 *
	 * @return string
	 */
	public function TagEncoder ($encoder = '') {
		if (func_num_args() != 0)
			$this->_tagEncoder = $encoder;

		return $this->_tagEncoder;
	}

	/**
	 * @param QuarkDate $date = null
	 *
	 * @return QuarkDate
	 */
	public function TagCreated (QuarkDate $date = null) {
		if (func_num_args() != 0)
			$this->_tagCreated = $date;

		return $this->_tagCreated;
	}

	/**
	 * @param FFmpegStream[] $streams = []
	 *
	 * @return FFmpegStream[]
	 */
	public function Streams ($streams = []) {
		if (func_num_args() != 0 && QuarkObject::IsArrayOf($streams, new FFmpegStream()))
			$this->_streams  =$streams;

		return $this->_streams;
	}

	/**
	 * @param FFmpegStream $stream = null
	 *
	 * @return FFmpegInfo
	 */
	public function Stream (FFmpegStream $stream = null) {
		if ($stream != null)
			$this->_streams[] = $stream;

		return $this;
	}
}