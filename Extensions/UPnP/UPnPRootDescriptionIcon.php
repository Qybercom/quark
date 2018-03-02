<?php
namespace Quark\Extensions\UPnP;

use Quark\QuarkXMLNode;

/**
 * Class UPnPRootDescriptionIcon
 *
 * @package Quark\Extensions\UPnP
 */
class UPnPRootDescriptionIcon {
	/**
	 * @var string $_url = ''
	 */
	private $_url = '';

	/**
	 * @var string $_type = ''
	 */
	private $_type = '';

	/**
	 * @var int $_width = 0
	 */
	private $_width = 0;

	/**
	 * @var int $_height = 0
	 */
	private $_height = 0;

	/**
	 * @var int $_depth = 24
	 */
	private $_depth = 24;

	/**
	 * @param string $url = ''
	 * @param string $type = ''
	 * @param int $width = 0
	 * @param int $height = 0
	 * @param int $depth = 24
	 */
	public function __construct ($url = '', $type = '', $width = 0, $height = 0, $depth = 24) {
		$this->URL($url);
		$this->Type($type);
		$this->Width($width);
		$this->Height($height);
		$this->ColorDepth($depth);
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
	 * @param int $depth = 24
	 *
	 * @return int
	 */
	public function ColorDepth ($depth = 24) {
		if (func_num_args() != 0)
			$this->_depth = $depth;

		return $this->_depth;
	}

	/**
	 * @return QuarkXMLNode
	 */
	public function ToXML () {
		return new QuarkXMLNode('icon', array(
			'mimeType' => $this->_type,
			'width' => $this->_width,
			'height' => $this->_height,
			'depth' => $this->_depth,
			'url' => $this->_url
		));
	}
}