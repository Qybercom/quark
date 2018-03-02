<?php
namespace Quark\Extensions\UPnP;

use Quark\QuarkXMLNode;

/**
 * Class UPnPServiceDescription
 *
 * @package Quark\Extensions\UPnP
 */
class UPnPServiceDescription {
	/**
	 * @var string $_type = ''
	 */
	private $_type = '';

	/**
	 * @var string $_id = ''
	 */
	private $_id = '';

	/**
	 * @var string $_urlDescription = ''
	 */
	private $_urlDescription = '';

	/**
	 * @var string $_urlControl = ''
	 */
	private $_urlControl = '';

	/**
	 * @var string $_urlEvent = ''
	 */
	private $_urlEvent = '';

	/**
	 * @param string $id = ''
	 * @param string $type = ''
	 * @param string $urlDescription = ''
	 * @param string $urlControl = ''
	 * @param string $urlEvent = ''
	 */
	public function __construct ($id = '', $type = '', $urlDescription = '', $urlControl = '', $urlEvent = '') {
		$this->ID($id);
		$this->Type($type);
		$this->URLDescription($urlDescription);
		$this->URLControl($urlControl);
		$this->URLEvent($urlEvent);
	}

	/**
	 * @param string $id = ''
	 *
	 * @return string
	 */
	public function ID ($id = '') {
		if (func_num_args() != 0)
			$this->_id = $id;

		return $this->_id;
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
	 * @param string $url = ''
	 *
	 * @return string
	 */
	public function URLDescription ($url = '') {
		if (func_num_args() != 0)
			$this->_urlDescription = $url;

		return $this->_urlDescription;
	}

	/**
	 * @param string $url = ''
	 *
	 * @return string
	 */
	public function URLControl ($url = '') {
		if (func_num_args() != 0)
			$this->_urlControl = $url;

		return $this->_urlControl;
	}

	/**
	 * @param string $url = ''
	 *
	 * @return string
	 */
	public function URLEvent ($url = '') {
		if (func_num_args() != 0)
			$this->_urlEvent = $url;

		return $this->_urlEvent;
	}

	/**
	 * @return QuarkXMLNode
	 */
	public function ToXML () {
		return new QuarkXMLNode('service', array(
			'serviceType' => $this->_type,
			'serviceId' => $this->_id,
			'SCPDURL' => $this->_urlDescription,
			'controlURL' => $this->_urlControl,
			'eventSubURL' => $this->_urlEvent
		));
	}
}