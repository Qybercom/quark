<?php
namespace Quark\Extensions\MediaProcessing\FFmpeg;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;

/**
 * Class FFmpegConfig
 *
 * @package Quark\Extensions\MediaProcessing\FFmpeg
 */
class FFmpegConfig implements IQuarkExtensionConfig {
	const FFMPEG = 'ffmpeg';
	const FFPROBE = 'ffprobe';

	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var string $_locationFFmpeg = ''
	 */
	private $_locationFFmpeg = '';

	/**
	 * @var string $_locationFFprobe = ''
	 */
	private $_locationFFprobe = '';

	/**
	 * @param string $locationFFmpeg = ''
	 * @param string $locationFFprobe = ''
	 */
	public function __construct ($locationFFmpeg = '', $locationFFprobe = '') {
		$this->LocationFFmpeg($locationFFmpeg);
		$this->LocationFFprobe($locationFFprobe);
	}

	/**
	 * @param string $location = ''
	 *
	 * @return FFmpegConfig
	 */
	public function Location ($location = '') {
		if (func_num_args() != 0) {
			$this->LocationFFmpeg($location . '/' . self::FFMPEG);
			$this->LocationFFprobe($location . '/' . self::FFPROBE);
		}

		return $this;
	}

	/**
	 * @param string $location = ''
	 *
	 * @return string
	 */
	public function LocationFFmpeg ($location = '') {
		if (func_num_args() != 0)
			$this->_locationFFmpeg = $location;

		return $this->_locationFFmpeg;
	}

	/**
	 * @param string $location = ''
	 *
	 * @return string
	 */
	public function LocationFFprobe ($location = '') {
		if (func_num_args() != 0)
			$this->_locationFFprobe = $location;

		return $this->_locationFFprobe;
	}

	/**
	 * @param string $name
	 */
	public function Stacked ($name) {
		$this->_name = $name;
	}

	/**
	 * @return string
	 */
	public function ExtensionName () {
		return $this->_name;
	}

	/**
	 * @param object $ini
	 *
	 * @return mixed
	 */
	public function ExtensionOptions ($ini) {
		if (isset($ini->Location))
			$this->Location($ini->Location);

		if (isset($ini->LocationFFmpeg))
			$this->LocationFFmpeg($ini->LocationFFmpeg);

		if (isset($ini->LocationFFprobe))
			$this->LocationFFprobe($ini->LocationFFprobe);
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new FFmpeg($this->_name);
	}
}