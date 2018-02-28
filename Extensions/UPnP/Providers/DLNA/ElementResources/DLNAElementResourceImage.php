<?php
namespace Quark\Extensions\UPnP\Providers\DLNA\ElementResources;

use Quark\QuarkObject;
use Quark\QuarkXMLNode;

use Quark\Extensions\UPnP\UPnPProperty;
use Quark\Extensions\UPnP\Providers\DLNA\IQuarkDLNAElementResource;
use Quark\Extensions\UPnP\Providers\DLNA\Elements\DLNAElementItem;

/**
 * Class DLNAElementResourceImage
 *
 * @package Quark\Extensions\UPnP\\Services\DLNA\ElementResources
 */
class DLNAElementResourceImage implements IQuarkDLNAElementResource {
	const PROFILE_JPEG = 'http-get:*:image/jpeg:DLNA.ORG_PN=JPEG_TN;DLNA.ORG_OP=00;DLNA.ORG_CI=0;DLNA.ORG_FLAGS=00D00000000000000000000000000000';
	const PROFILE_PNG = 'http-get:*:image/png:*';
	const PROFILE_GIF = 'http-get:*:image/gif:*';
	const PROFILE_TIFF = 'http-get:*:image/tiff:*';

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
	 * @var int $_colorDepth = 24
	 */
	private $_colorDepth = 24;

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
	 * @param int $colorDepth = 0
	 * @param string $info = ''
	 */
	public function __construct ($url = '', $type = '', $size = 0, $width = 0, $height = 0, $colorDepth = 24, $info = '') {
		$this->URL($url);
		$this->Type($type);
		$this->Size($size);
		$this->Width($width);
		$this->Height($height);
		$this->ColorDepth($colorDepth);

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
	 * @param int $colorDepth = 0
	 *
	 * @return int
	 */
	public function ColorDepth ($colorDepth = 0) {
		if (func_num_args() != 0)
			$this->_colorDepth = $colorDepth;

		return $this->_colorDepth;
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
	 * @return QuarkXMLNode
	 */
	public function DLNAElementResource () {
		return new QuarkXMLNode(DLNAElementItem::RESOURCE, $this->_url, array(
			'protocolInfo' => $this->_protocolInfo,
			'size' => $this->_size,
			'resolution' => $this->_width . 'x' . $this->_height,
			'colorDepth' => $this->_colorDepth
		));
	}

	/**
	 * @return UPnPProperty[]
	 */
	public function DLNAElementResourceUPnPProperties () {
		return array(
			new UPnPProperty(
				DLNAElementItem::PROPERTY_UPnP_CLASS,
				DLNAElementItem::UPnP_CLASS_IMAGE
			)
		);
	}
}