<?php
namespace Quark\ViewResources\Preprocessors\LESS;

use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkForeignViewResource;

use Quark\QuarkDTO;
use Quark\QuarkJSViewResourceType;

/**
 * Class LESSClientCompiler
 *
 * @package Quark\ViewResources\Preprocessors\LESS
 */
class LESSClientCompiler implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource {
	const CURRENT_VERSION = '2.7.1';

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
		return 'https://cdnjs.cloudflare.com/ajax/libs/less.js/' . $this->_version . '/less.min.js';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}