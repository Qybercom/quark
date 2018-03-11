<?php
namespace Quark\Extensions\UPnP\Providers\DLNA\ElementResources;

use Quark\QuarkCollection;
use Quark\QuarkFile;
use Quark\QuarkKeyValuePair;
use Quark\QuarkObject;

use Quark\Extensions\UPnP\Providers\DLNA\IQuarkDLNAElementResource;
use Quark\Extensions\UPnP\Providers\DLNA\DLNAElement;
use Quark\Extensions\UPnP\Providers\DLNA\DLNAElementProperty;

/**
 * Class DLNAElementResourceVideo
 *
 * @package Quark\Extensions\UPnP\Providers\DLNA\ElementResources
 */
class DLNAElementResourceVideo implements IQuarkDLNAElementResource {
	const PROFILE_MKV = 'http-get:*:video/x-matroska:DLNA.ORG_PN=MATROSKA;DLNA.ORG_OP=01;DLNA.ORG_CI=0;DLNA.ORG_FLAGS=01500000000000000000000000000000';

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
	 * @var int $_width = 0
	 */
	private $_width = 0;

	/**
	 * @var int $_height = 0
	 */
	private $_height = 0;

	/**
	 * @var string $_duration = ''
	 */
	private $_duration = '';

	/**
	 * @var int $_bitRate = 0
	 */
	private $_bitRate = 0;

	/**
	 * @var string $_protocolInfo = ''
	 */
	private $_protocolInfo = '';

	/**
	 * @param string $url = ''
	 * @param string $type = ''
	 * @param int $size = 0
	 * @param int $width = 0
	 * @param int $height = 0
	 * @param string $duration = ''
	 * @param int $bitRate = 0
	 * @param string $info = ''
	 */
	public function __construct ($url = '', $type = '', $size = 0, $width = 0, $height = 0, $duration = '', $bitRate = 0, $info = '') {
		$this->URL($url);
		$this->Type($type);
		$this->Size($size);
		$this->Width($width);
		$this->Height($height);
		$this->Duration($duration);
		$this->BitRate($bitRate);
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
	 * @param int $bitRate = 0
	 *
	 * @return int
	 */
	public function BitRate ($bitRate = 0) {
		if (func_num_args() != 0)
			$this->_bitRate = $bitRate;

		return $this->_bitRate;
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
	 * @return string
	 */
	public function DLNAElementResourceURL () {
		return $this->_url;
	}

	/**
	 * @return QuarkKeyValuePair[]
	 */
	public function DLNAElementResourceAttributes () {
		if ($this->_protocolInfo == '')
			$this->ProtocolInfo(QuarkObject::ClassConstValue($this, 'PROFILE_' . strtoupper(array_reverse(explode('/', $this->_type))[0])));

		return array(
			new QuarkKeyValuePair('bitrate', $this->_bitRate),
			new QuarkKeyValuePair('duration', $this->_duration),
			new QuarkKeyValuePair('protocolInfo', $this->_protocolInfo),
			new QuarkKeyValuePair('resolution', $this->_width . 'x' . $this->_height),
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
			'value' => DLNAElement::UPnP_CLASS_ITEM_VIDEO
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

	/**
	 * @param QuarkFile $file = null
	 *
	 * @return DLNAElementResourceImage
	 */
	public static function FromFile (QuarkFile $file = null) {
		if ($file == null) return null;

		// TODO: add solution of manipulation with video files

		return new self($file->WebLocation(), $file->TypeOnDisk(), $file->SizeOnDisk());
	}
}