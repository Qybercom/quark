<?php
namespace Quark\ViewResources\jQuery;

use Quark\IQuarkViewResource;
use Quark\IQuarkForeignViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkDTO;
use Quark\QuarkJSViewResourceType;

/**
 * Class Core
 *
 * @package Quark\ViewResources\jQuery
 */
class jQueryCore implements IQuarkViewResource, IQuarkForeignViewResource {
	const CURRENT_VERSION = '2.1.1';

	/**
	 * @var string $_version = self::CURRENT_VERSION
	 */
	private $_version = self::CURRENT_VERSION;

	/**
	 * @param string $version = self::CURRENT_VERSION
	 */
	public function __construct ($version = self::CURRENT_VERSION) {
		$this->_version = $version;
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
		return 'https://code.jquery.com/jquery-' . $this->_version . '.min.js';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}