<?php
namespace Quark\Extensions\MediaProcessing\FFmpeg;

use Quark\IQuarkExtension;

use Quark\Quark;
use Quark\QuarkArchException;
use Quark\QuarkCLIBehavior;
use Quark\QuarkDate;
use Quark\QuarkDateInterval;
use Quark\QuarkFile;

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
	 * @param string $bin = ''
	 * @param string $command = ''
	 * @param array $output = []
	 * @param int $status = 0
	 *
	 * @return bool
	 *
	 * @throws QuarkArchException
	 */
	private function _command ($bin = '', $command = '', &$output = [], &$status = 0) {
		if (trim($bin) == '')
			throw new QuarkArchException('FFmpeg binary path does not configured properly. Trying to execute "' . $bin . '" with arguments "' . $command . '"');

		return $this->Shell($bin . ' ' . $command, $output, $status);
	}

	/**
	 * @param string $command = ''
	 * @param string[] &$output = []
	 * @param int &$status = 0
	 *
	 * @return bool
	 */
	public function Command ($command = '', &$output = [], &$status = 0) {
		return $this->_command($this->_config->LocationFFmpeg(), $command, $output, $status);
	}

	/**
	 * @param string $command = ''
	 * @param string[] &$output = []
	 * @param int &$status = 0
	 *
	 * @return bool
	 */
	public function CommandProbe ($command = '', &$output = [], &$status = 0) {
		return $this->_command($this->_config->LocationFFprobe(), $command, $output, $status);
	}

	/**
	 * @param string $file = ''
	 *
	 * @return FFmpegInfo
	 */
	public function Info ($file = '') {
		$command = '-print_format json -show_streams -show_format -v quiet "' . $file . '"';

		if (!$this->CommandProbe($command, $output, $status)) {
			Quark::Log('[FFmpeg] Can not retrieve info from "' . $file . '". Command: ' . $command . '. Status: ' . $status, Quark::LOG_WARN);
			Quark::Trace($output);
			return null;
		}

		$out = json_decode(implode('', $this->_shellOutput));
		if (!isset($out->format) || !isset($out->streams)) return null;

		$info = new FFmpegInfo($out->format->filename, (int)$out->format->size);

		if (isset($out->format->format_name)) $info->FormatName($out->format->format_name);
		if (isset($out->format->format_long_name)) $info->FormatNameLong($out->format->format_long_name);
		if (isset($out->format->start_time)) $info->Start($out->format->start_time);
		if (isset($out->format->duration)) $info->Duration(QuarkDateInterval::FromSeconds($out->format->duration));
		if (isset($out->format->bit_rate)) $info->BitRate((int)$out->format->bit_rate);
		if (isset($out->format->tags->title)) $info->TagTitle($out->format->tags->title);
		if (isset($out->format->tags->encoder)) $info->TagEncoder($out->format->tags->encoder);
		if (isset($out->format->tags->creation_time)) $info->TagCreated(QuarkDate::GMTOf($out->format->tags->creation_time));

		foreach ($out->streams as $i => &$stream)
			$info->Stream(FFmpegStream::FromProbe($stream));

		return $info;
	}

	/**
	 * https://superuser.com/a/704118/568233
	 *
	 * @param QuarkFile $file = null
	 * @param string $start = '0'
	 * @param string $duration = '00:00:01'
	 * @param string $format = 'mp4'
	 *
	 * @return QuarkFile
	 *
	 * @throws QuarkArchException
	 */
	public function Fragment (QuarkFile $file = null, $start = '0', $duration = '00:00:01', $format = 'mp4') {
		if ($file == null) return null;

		$tmp = Quark::TempFile('ffmpeg_fragment');
		$tmp->Rename($tmp->name . '.' . $format, true);

		$command = '-y -ss ' . $start . ' -t ' . $duration . ' -i "' . $file->Location() . '" -c copy -v quiet ' . $tmp->Location();

		$output = '';
		if (!$this->Command($command, $output, $status)) {
			Quark::Log('[FFmpeg] Can not get fragment from "' . $file->Location() . '". Command: ' . $command, Quark::LOG_WARN);
			return null;
		}

		return $tmp;
	}

	/**
	 * https://ffmpeg.org/ffmpeg.html#Filtering
	 * https://networking.ringofsaturn.com/Unix/extractthumbnail.php
	 * https://trac.ffmpeg.org/wiki/Scaling
	 *
	 * @param QuarkFile $file = null
	 * @param string $moment = '0'
	 * @param string $format = 'apng'
	 * @param int $width = -1
	 * @param int $height = -1
	 *
	 * @return QuarkFile
	 *
	 * @throws QuarkArchException
	 */
	public function Frame (QuarkFile $file = null, $moment = '0', $format = 'apng', $width = -1, $height = -1) {
		if ($file == null) return null;

		$tmp = Quark::TempFile('ffmpeg_frame');

		$command = '-y -ss ' . $moment . ' -t 00:00:01 -i "' . $file->Location() . '" -vf "scale=' . $width . ':' . $height . '" -vsync 0 -f ' . $format . ' -vframes 1 -an -v quiet ' . $tmp->Location();

		if (!$this->Command($command)) {
			Quark::Log('[FFmpeg] Can not get frame from "' . $file->Location() . '". Command: ' . $command, Quark::LOG_WARN);
			return null;
		}

		$tmp->Load();

		return $tmp->DeleteFromDisk() ? $tmp : null;
	}

	/**
	 * @param QuarkFile $file = null
	 * @param string $format = 'apng'
	 * @param int $width = -1
	 * @param int $height = -1
	 *
	 * @return QuarkFile
	 */
	public function FrameRandom (QuarkFile $file = null, $format = 'apng', $width = -1, $height = -1) {
		if ($file == null) return null;

		$info = $this->Info($file->Location());

		$duration = $info->Duration()->Seconds();
		$moment = QuarkDateInterval::FromSeconds(mt_rand(0, $duration));

		return $this->Frame($file, $moment->Format('H:i:s'), $format, $width, $height);
	}

	/**
	 * https://itectec.com/superuser/meaningful-thumbnails-for-a-video-using-ffmpeg/
	 * https://mediamachine.io/blog/art-and-science-of-a-great-video-thumbnail/?utm_campaign=stackoverflow+answer&utm_source=stackoverflow&utm_medium=social
	 *
	 * @param QuarkFile $file = null
	 * @param string $format = 'apng'
	 * @param int $width = -1
	 * @param int $height = -1
	 * @param float $changes = 0.4
	 * @param string $fps = '1/600'
	 *
	 * @return QuarkFile
	 *
	 * @throws QuarkArchException
	 */
	public function Thumbnail (QuarkFile $file = null, $format = 'apng', $width = -1, $height = -1, $changes = 0.4, $fps = '1/600') {
		if ($file == null) return null;

		$tmp = Quark::TempFile('ffmpeg_frame');

		$command = '-y -i "' . $file->Location() . '" -vf "select=gt(scene\,' . $changes . '),fps=fps=' . $fps . ',scale=' . $width . ':' . $height . '" -vframes 1 -vsync vfr -f ' . $format . ' -vframes 1 -an -v quiet ' . $tmp->Location();

		if (!$this->Command($command)) {
			Quark::Log('[FFmpeg] Can not get frame from "' . $file->Location() . '". Command: ' . $command, Quark::LOG_WARN);
			return null;
		}

		$tmp->Load();

		return $tmp->DeleteFromDisk() ? $tmp : null;
	}
}