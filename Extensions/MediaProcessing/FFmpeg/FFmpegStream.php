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
	const CHANNEL_LAYOUT_5_1_SIDE = '5.1(side)';

	const FIELD_ORDER_PROGRESSIVE = 'progressive';

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
	 * @var string $_sampleFormat = ''
	 */
	private $_sampleFormat = '';

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
	 * @var string $_pixelFormat = ''
	 */
	private $_pixelFormat = '';

	/**
	 * @var int $_width = 0
	 */
	private $_width = 0;

	/**
	 * @var int $_height = 0
	 */
	private $_height = 0;

	/**
	 * @var string $_aspectRatioSample = ''
	 */
	private $_aspectRatioSample = '';

	/**
	 * @var string $_aspectRatioDisplay = ''
	 */
	private $_aspectRatioDisplay = '';

	/**
	 * @var string $_fieldOrder = ''
	 */
	private $_fieldOrder = '';

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
	 * @var string $_tagLanguage = ''
	 */
	private $_tagLanguage = '';

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
	 * @param string $format = ''
	 *
	 * @return string
	 */
	public function SampleFormat ($format = '') {
		if (func_num_args() != 0)
			$this->_sampleFormat = $format;

		return $this->_sampleFormat;
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
	 * @param string $format = ''
	 *
	 * @return string
	 */
	public function PixelFormat ($format = '') {
		if (func_num_args() != 0)
			$this->_pixelFormat = $format;

		return $this->_pixelFormat;
	}

	/**
	 * @param int $width = 0
	 *
	 * @return int
	 */
	public function Width ($width = 0) {
		if (func_num_args() != 0)
			$this->_width = $width;

		return $this->_width;
	}

	/**
	 * @param int $height = 0
	 *
	 * @return int
	 */
	public function Height ($height = 0) {
		if (func_num_args() != 0)
			$this->_height = $height;

		return $this->_height;
	}

	/**
	 * @param string $ratio = ''
	 *
	 * @return string
	 */
	public function AspectRatioSample ($ratio = '') {
		if (func_num_args() != 0)
			$this->_aspectRatioSample = $ratio;

		return $this->_aspectRatioSample;
	}

	/**
	 * @param string $ratio = ''
	 *
	 * @return string
	 */
	public function AspectRatioDisplay ($ratio = '') {
		if (func_num_args() != 0)
			$this->_aspectRatioDisplay = $ratio;

		return $this->_aspectRatioDisplay;
	}

	/**
	 * @param string $fieldOrder = ''
	 *
	 * @return string
	 */
	public function FieldOrder ($fieldOrder = '') {
		if (func_num_args() != 0)
			$this->_fieldOrder = $fieldOrder;

		return $this->_fieldOrder;
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
	 * @param string $language = ''
	 *
	 * @return string
	 */
	public function TagLanguage ($language = '') {
		if (func_num_args() != 0)
			$this->_tagLanguage = $language;

		return $this->_tagLanguage;
	}

	/**
	 * @param array|object $info = []
	 *
	 * @return FFmpegStream
	 */
	public static function FromProbe ($info = []) {
		$info = (object)$info;

		$out = new self();

		if (isset($info->index)) $out->Index((int)$info->index);
		if (isset($info->codec_name)) $out->CodecName($info->codec_name);
		if (isset($info->codec_long_name)) $out->CodecNameLong($info->codec_long_name);
		if (isset($info->codec_type)) $out->CodecType($info->codec_type);
		if (isset($info->codec_tag)) $out->CodecTag($info->codec_tag);
		if (isset($info->sample_fmt)) $out->SampleFormat($info->sample_fmt);
		if (isset($info->sample_rate)) $out->SampleBitRate($info->sample_rate);
		if (isset($info->channels)) $out->ChannelCount((int)$info->channels);
		if (isset($info->channel_layout)) $out->ChannelLayout($info->channel_layout);
		if (isset($info->bits_per_sample)) $out->SampleBitCount($info->bits_per_sample);
		if (isset($info->r_frame_rate)) $out->FrameRate($info->r_frame_rate);
		if (isset($info->pix_fmt)) $out->PixelFormat($info->pix_fmt);
		if (isset($info->width)) $out->Width((int)$info->width);
		if (isset($info->height)) $out->Height((int)$info->height);
		if (isset($info->sample_aspect_ratio)) $out->AspectRatioSample($info->sample_aspect_ratio);
		if (isset($info->display_aspect_ratio)) $out->AspectRatioDisplay($info->display_aspect_ratio);
		if (isset($info->field_order)) $out->FieldOrder($info->field_order);
		if (isset($info->start_pts)) $out->Start($info->start_pts);
		if (isset($info->duration)) $out->Duration(QuarkDateInterval::FromSeconds($info->duration));
		if (isset($info->bit_rate)) $out->BitRate((int)$info->bit_rate);
		if (isset($info->tags->title)) $out->TagTitle($info->tags->title);
		if (isset($info->tags->language)) $out->TagLanguage($info->tags->language);

		return $out;
	}
}