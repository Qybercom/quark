<?php
namespace Quark\Extensions\MediaProcessing\FFmpeg;

use Quark\IQuarkExtension;

use Quark\Quark;
use Quark\QuarkCLIBehavior;
use Quark\QuarkDate;
use Quark\QuarkDateInterval;

/**
 * Class FFmpeg
 *
 * @package Quark\Extensions\MediaProcessing\FFmpeg
 */
class FFmpeg implements IQuarkExtension {
	use QuarkCLIBehavior;

	/**
	 * @var FFmpegConfig $_config
	 */
	private $_config;

	/**
	 * @param string $config = ''
	 */
	public function __construct ($config = '') {
		$this->_config = Quark::Config()->Extension($config);
	}

	/**
	 * @return FFmpegConfig
	 */
	public function &Config () {
		return $this->_config;
	}

	/**
	 * @param string $file = ''
	 *
	 * @return FFmpegInfo
	 */
	public function Info ($file = '') {
		$command = $this->_config->LocationFFprobe() . ' -print_format json -show_streams -show_format -v quiet "' . $file . '"';

		if (!$this->Shell($command)) {
			Quark::Log('[FFmpeg] Can not retrieve info from "' . $file . '". Command: ' . $command, Quark::LOG_WARN);
			return null;
		}

		$out = json_decode(implode('', $this->_shellOutput));
		if (!isset($out->format) || !isset($out->streams)) return null;

		$info = new FFmpegInfo($out->format->filename, $out->format->size);

		Quark::Trace($out);
		if (isset($out->format->format_name)) $info->FormatName($out->format->format_name);
		if (isset($out->format->format_long_name)) $info->FormatNameLong($out->format->format_long_name);
		if (isset($out->format->start_time)) $info->Start($out->format->start_time);
		if (isset($out->format->duration)) $info->Duration(QuarkDateInterval::FromSeconds($out->format->duration));
		if (isset($out->format->bit_rate)) $info->BitRate($out->format->bit_rate);
		if (isset($out->format->tags->title)) $info->TagTitle($out->format->tags->title);
		if (isset($out->format->tags->encoder)) $info->TagEncoder($out->format->tags->encoder);
		if (isset($out->format->tags->creation_time)) $info->TagCreated(QuarkDate::GMTOf($out->format->tags->creation_time));

		foreach ($out->streams as $i => &$stream)
			$info->Stream(FFmpegStream::FromProbe($stream));

		return $info;
	}
}