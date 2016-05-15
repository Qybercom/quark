<?php
namespace Quark\ViewResources\HighlightJS;

use Quark\IQuarkForeignViewResource;
use Quark\IQuarkMultipleViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkDTO;
use Quark\QuarkJSViewResourceType;

/**
 * Class HighlightJSLanguage
 *
 * @package Quark\ViewResources\HighlightJS
 */
class HighlightJSLanguage implements IQuarkViewResource, IQuarkForeignViewResource, IQuarkMultipleViewResource {
	const PHP = 'php';
	const CSS = 'css';
	const JAVASCRIPT = 'javascript';
	const JSON = 'json';

	/**
	 * @var string $_version = HighlightJS::CURRENT_VERSION
	 */
	private $_version = HighlightJS::CURRENT_VERSION;

	/**
	 * @var string $_name = self::PHP
	 */
	private $_name = self::PHP;

	/**
	 * @param string $name = self::PHP
	 * @param string $version = HighlightJS::CURRENT_VERSION
	 */
	public function __construct ($name = self::PHP, $version = HighlightJS::CURRENT_VERSION) {
		$this->_name = $name;
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
		return 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/' . $this->_version . '/languages/' . $this->_name . '.min.js';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}