<?php
namespace Quark\ViewResources\jQuery;

use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkForeignViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkDTO;
use Quark\QuarkJSViewResourceType;

/**
 * Class Core
 *
 * @package Quark\ViewResources\jQuery
 */
class jQueryCore implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource {
	const CURRENT_VERSION = '2.2.4';

	/**
	 * @var string $_version = self::CURRENT_VERSION
	 */
	private $_version = self::CURRENT_VERSION;

	/**
	 * @var bool $_cdnJS = true
	 */
	private $_cdnJS = true;

	/**
	 * @param string $version = self::CURRENT_VERSION
	 * @param bool $cdnJS = true
	 */
	public function __construct ($version = self::CURRENT_VERSION, $cdnJS = true) {
		$this->_version = $version;
		$this->_cdnJS = $cdnJS;
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
		return $this->_cdnJS
			? ('https://cdnjs.cloudflare.com/ajax/libs/jquery/' . $this->_version . '/jquery.min.js')
			: ('https://code.jquery.com/jquery-' . $this->_version . '.min.js');
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}