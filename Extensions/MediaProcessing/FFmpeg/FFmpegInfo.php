<?php
namespace Quark\Extensions\MediaProcessing\FFmpeg;

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