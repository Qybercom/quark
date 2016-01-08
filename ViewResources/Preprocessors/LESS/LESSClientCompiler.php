<?php
namespace Quark\ViewResources\Preprocessors\LESS;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkJSViewResourceType;

/**
 * Class LESSClientCompiler
 *
 * @package Quark\ViewResources\Preprocessors\LESS
 */
class LESSClientCompiler implements IQuarkViewResource {
	const CURRENT_VERSION = '2.5.3';

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
}