<?php
namespace Quark\ViewResources\Google;

use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkForeignViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkDTO;
use Quark\QuarkJSViewResourceType;

/**
 * Class GoogleMapAPI
 *
 * @package Quark\ViewResources\Google
 */
class GoogleMapAPI implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource {
	/**
	 * @var string $_key = ''
	 */
	private $_key = '';

	/**
	 * @param string $key
	 */
	public function __construct ($key = '') {
		$this->_key = $key;
	}

	/**
	 * @return IQuarkViewResourceType
	 */
	public function Type () {
		return new QuarkJSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return 'https://maps.googleapis.com/maps/api/js?libraries=geometry&sensor=false'. (strlen($this->_key) != 0 ? '&key=' . $this->_key : '');
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}