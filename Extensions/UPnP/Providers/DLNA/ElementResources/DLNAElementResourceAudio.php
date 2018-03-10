<?php
namespace Quark\Extensions\UPnP\Providers\DLNA\ElementResources;

use Quark\QuarkCollection;
use Quark\QuarkKeyValuePair;
use Quark\QuarkObject;

use Quark\Extensions\UPnP\Providers\DLNA\IQuarkDLNAElementResource;
use Quark\Extensions\UPnP\Providers\DLNA\DLNAElement;
use Quark\Extensions\UPnP\Providers\DLNA\DLNAElementProperty;

/**
 * Class DLNAElementResourceAudio
 *
 * @package Quark\Extensions\UPnP\Providers\DLNA\ElementResources
 */
class DLNAElementResourceAudio implements IQuarkDLNAElementResource {
	const PROFILE_MPEG = 'http-get:*:audio/mpeg:DLNA.ORG_PN=MP3;DLNA.ORG_OP=01;DLNA.ORG_CI=0;DLNA.ORG_FLAGS=01500000000000000000000000000000';

	const BITRATE = 24000;
	const SAMPLE_FREQUENCY = 44100;

	const CHANNELS_1_0 = 1.0;
	const CHANNELS_2_0 = 2.0;
	const CHANNELS_2_1 = 2.1;
	const CHANNELS_5_1 = 5.1;
	const CHANNELS_7_1 = 7.1;

	/**
	 * @var string $_url = ''
	 */
	private $_url = '';

	/**
	 * @var string $_type = ''
	 */
	private $_type = '';

	/**
	 * @var int $_size = 0
	 */
	private $_size = 0;

	/**
	 * @var string $_duration = ''
	 */
	private $_duration = '';

	/**
	 * @var float|int $_channels = self::CHANNELS_2_0
	 */
	private $_channels = self::CHANNELS_2_0;

	/**
	 * @var int $_bitRate = self::BITRATE
	 */
	private $_bitRate = self::BITRATE;

	/**
	 * @var int $_sampleFrequency = self::SAMPLE_FREQUENCY
	 */
	private $_sampleFrequency = self::SAMPLE_FREQUENCY;

	/**
	 * @var string $_protocolInfo = ''
	 */
	private $_protocolInfo = '';

	/**
	 * @param string $url = ''
	 * @param string $type = ''
	 * @param int $size = 0
	 * @param string $duration = ''
	 * @param float|int $channels = self::CHANNELS_2_0
	 * @param int $bitRate = self::BITRATE
	 * @param int $sampleFrequency = self::SAMPLE_FREQUENCY
	 * @param string $info = ''
	 */
	public function __construct ($url = '', $type = '', $size = 0, $duration = '', $channels = self::CHANNELS_2_0, $bitRate = self::BITRATE, $sampleFrequency = self::SAMPLE_FREQUENCY, $info = '') {
		$this->URL($url);
		$this->Type($type);
		$this->Size($size);
		$this->Duration($duration);
		$this->Channels($channels);
		$this->BitRate($bitRate);
		$this->SampleFrequency($sampleFrequency);

		if ($info == '')
			$this->ProtocolInfo(QuarkObject::ClassConstValue($this, 'PROFILE_' . strtoupper(array_reverse(explode('/', $type))[0])));
	}

	/**
	 * @param string $url = ''
	 *
	 * @return string
	 */
	public function URL ($url = '') {
		if (func_num_args() != 0)
			$this->_url = $url;

		return $this->_url;
	}

	/**
	 * @param string $type = ''
	 *
	 * @return string
	 */
	public function Type ($type = '') {
		if (func_num_args() != 0)
			$this->_type = $type;

		return $this->_type;
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
	 * @param string $info = ''
	 *
	 * @return string
	 */
	public function ProtocolInfo ($info = '') {
		if (func_num_args() != 0)
			$this->_protocolInfo = $info;

		return $this->_protocolInfo;
	}

	/**
	 * @param string $duration = ''
	 *
	 * @return string
	 */
	public function Duration ($duration = '') {
		if (func_num_args() != 0)
			$this->_duration = $duration;

		return $this->_duration;
	}

	/**
	 * @param float|int $channels = self::CHANNELS_2_0
	 *
	 * @return float|int
	 */
	public function Channels ($channels = self::CHANNELS_2_0) {
		if (func_num_args() != 0)
			$this->_channels = $channels;

		return $this->_channels;
	}

	/**
	 * @param int $bitRate = self::BITRATE
	 *
	 * @return int
	 */
	public function BitRate ($bitRate = self::BITRATE) {
		if (func_num_args() != 0)
			$this->_bitRate = $bitRate;

		return $this->_bitRate;
	}

	/**
	 * @param int $sampleFrequency = self::SAMPLE_FREQUENCY
	 *
	 * @return int
	 */
	public function SampleFrequency ($sampleFrequency = self::SAMPLE_FREQUENCY) {
		if (func_num_args() != 0)
			$this->_sampleFrequency = $sampleFrequency;

		return $this->_sampleFrequency;
	}

	/**
	 * @return string
	 */
	public function DLNAElementResourceURL () {
		return $this->_url;
	}

	/**
	 * @return QuarkKeyValuePair[]
	 */
	public function DLNAElementResourceAttributes () {
		return array(
			new QuarkKeyValuePair('bitrate', $this->_bitRate),
			new QuarkKeyValuePair('duration', $this->_duration),
			new QuarkKeyValuePair('nrAudioChannels', $this->_channels),
			new QuarkKeyValuePair('protocolInfo', $this->_protocolInfo),
			new QuarkKeyValuePair('sampleFrequency', $this->_sampleFrequency),
			new QuarkKeyValuePair('size', $this->_size)
		);
	}

	/**
	 * @return QuarkCollection|DLNAElementProperty[]
	 */
	public function DLNAElementResourceItemProperties () {
		$out = new QuarkCollection(new DLNAElementProperty());

		$out->AddBySource(array(
			'name' => DLNAElement::PROPERTY_UPnP_CLASS,
			'value' => DLNAElement::UPnP_CLASS_ITEM_AUDIO
		));

		$attributes = array();
		$resourceAttributes = $this->DLNAElementResourceAttributes();

		foreach ($resourceAttributes as $i => &$attribute)
			$attributes[$attribute->Key()] = $attribute->Value();

		$out->AddBySource(array(
			'name' => DLNAElement::PROPERTY_RESOURCE,
			'value' => $this->DLNAElementResourceURL(),
			'attributes' => $attributes
		));

		return $out;
	}
}