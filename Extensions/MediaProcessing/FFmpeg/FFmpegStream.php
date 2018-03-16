<?php
namespace Quark\Extensions\MediaProcessing\FFmpeg;

use Quark\QuarkDateInterval;

/**
 * Class FFmpegStream
 *
 * @package Quark\Extensions\MediaProcessing\FFmpeg
 */
class FFmpegStream {
	const CODEC_AUDIO = 'audio';
	const CODEC_VIDEO = 'video';

	const CHANNEL_LAYOUT_STEREO = 'stereo';

	/**
	 * @var int $_index = 0
	 */
	private $_index = 0;

	/**
	 * @var string $_codecName = ''
	 */
	private $_codecName = '';

	/**
	 * @var string $_codecNameLong = ''
	 */
	private $_codecNameLong = '';

	/**
	 * @var string $_codecType = ''
	 */
	private $_codecType = '';

	/**
	 * @var string $_codecTag = ''
	 */
	private $_codecTag = '';

	/**
	 * @var string $_sampleFmt = ''
	 */
	private $_sampleFmt = '';

	/**
	 * @var int $_sampleBitRate = 0
	 */
	private $_sampleBitRate = 0;

	/**
	 * @var int $_sampleBitCount = 0
	 */
	private $_sampleBitCount = 0;

	/**
	 * @var int $_channelCount = 0
	 */
	private $_channelCount = 2;

	/**
	 * @var string $_channelLayout = ''
	 */
	private $_channelLayout = '';

	/**
	 * @var string $_frameRate = '0/0'
	 */
	private $_frameRate = '0/0';

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
	 * @param int $index = 0
	 *
	 * @return int
	 */
	public function Index ($index = 0) {
		if (func_num_args() != 0)
			$this->_index = $index;

		return $this->_index;
	}

	/**
	 * @param string $name = ''
	 *
	 * @return string
	 */
	public function CodecName ($name = '') {
		if (func_num_args() != 0)
			$this->_codecName = $name;

		return $this->_codecName;
	}

	/**
	 * @param string $name = ''
	 *
	 * @return string
	 */
	public function CodecNameLong ($name = '') {
		if (func_num_args() != 0)
			$this->_codecNameLong = $name;

		return $this->_codecNameLong;
	}

	/**
	 * @param string $type = ''
	 *
	 * @return string
	 */
	public function CodecType ($type = '') {
		if (func_num_args() != 0)
			$this->_codecType = $type;

		return $this->_codecType;
	}

	/**
	 * @param string $tag = ''
	 *
	 * @return string
	 */
	public function CodecTag ($tag = '') {
		if (func_num_args() != 0)
			$this->_codecTag = $tag;

		return $this->_codecTag;
	}

	/**
	 * @param string $fmt = ''
	 *
	 * @return string
	 */
	public function SampleFmt ($fmt = '') {
		if (func_num_args() != 0)
			$this->_sampleFmt = $fmt;

		return $this->_sampleFmt;
	}

	/**
	 * @param int $bitRate = 0
	 *
	 * @return int
	 */
	public function SampleBitRate ($bitRate = 0) {
		if (func_num_args() != 0)
			$this->_sampleBitRate = $bitRate;

		return $this->_sampleBitRate;
	}

	/**
	 * @param int $bitCount = 0
	 *
	 * @return int
	 */
	public function SampleBitCount ($bitCount = 0) {
		if (func_num_args() != 0)
			$this->_sampleBitCount = $bitCount;

		return $this->_sampleBitCount;
	}

	/**
	 * @param int $count = 0
	 *
	 * @return int
	 */
	public function ChannelCount ($count = 0) {
		if (func_num_args() != 0)
			$this->_channelCount = $count;

		return $this->_channelCount;
	}

	/**
	 * @param string $layout = ''
	 *
	 * @return string
	 */
	public function ChannelLayout ($layout = '') {
		if (func_num_args() != 0)
			$this->_channelLayout = $layout;

		return $this->_channelLayout;
	}

	/**
	 * @param string $rate = '0/0'
	 *
	 * @return string
	 */
	public function FrameRate ($rate = '0/0') {
		if (func_num_args() != 0)
			$this->_frameRate = $rate;

		return $this->_frameRate;
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
	 * @param array|object $info = []
	 *
	 * @return FFmpegStream
	 */
	public static function FromProbe ($info = []) {
		$info = (object)$info;

		$out = new self();

		if (isset($info->index)) $out->Index($info->index);
		if (isset($info->codec_name)) $out->CodecName($info->codec_name);
		if (isset($info->codec_long_name)) $out->CodecNameLong($info->codec_long_name);
		if (isset($info->codec_type)) $out->CodecType($info->codec_type);
		if (isset($info->codec_tag)) $out->CodecTag($info->codec_tag);
		if (isset($info->sample_fmt)) $out->SampleFmt($info->sample_fmt);
		if (isset($info->sample_rate)) $out->SampleBitRate($info->sample_rate);
		if (isset($info->channels)) $out->ChannelCount($info->channels);
		if (isset($info->channel_layout)) $out->ChannelLayout($info->channel_layout);
		if (isset($info->bits_per_sample)) $out->SampleBitCount($info->bits_per_sample);
		if (isset($info->r_frame_rate)) $out->FrameRate($info->r_frame_rate);
		if (isset($info->start_pts)) $out->Start($info->start_pts);
		if (isset($info->duration)) $out->Duration(QuarkDateInterval::FromSeconds($info->duration));
		if (isset($info->bit_rate)) $out->BitRate($info->bit_rate);

		return $out;
	}
}