<?php
namespace Quark\ViewResources\FontAwesome;

use Quark\IQuarkViewResource;
use Quark\IQuarkForeignViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkCSSViewResourceType;
use Quark\QuarkDTO;

/**
 * Class FontAwesome
 *
 * @package Quark\ViewResources\FontAwesome
 */
class FontAwesome implements IQuarkViewResource, IQuarkForeignViewResource {
	private $_version = '';

	/**
	 * @param string $_version = '4.4.0'
	 */
	public function __construct ($_version = '4.4.0') {
		$this->_version = $_version;
	}

	/**
	 * @return IQuarkViewResourceType
	 */
	public function Type () {
		return new QuarkCSSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return '//maxcdn.bootstrapcdn.com/font-awesome/' . $this->_version . '/css/font-awesome.min.css';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}