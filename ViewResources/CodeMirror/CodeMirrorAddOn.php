<?php
namespace Quark\ViewResources\CodeMirror;

use Quark\IQuarkForeignViewResource;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResourceType;
use Quark\QuarkDTO;
use Quark\QuarkJSViewResourceType;

/**
 * Class CodeMirrorAddOn
 *
 * @package Quark\ViewResources\CodeMirror
 */
class CodeMirrorAddOn implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource {
	/**
	 * @var string $_name
	 */
	private $_name;

	/**
	 * @var string $_version
	 */
	private $_version = CodeMirror::VERSION_CURRENT;

	/**
	 * @param string $name
	 * @param string $version
	 */
	public function __construct ($name, $version = CodeMirror::VERSION_CURRENT) {
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
		return 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/' . $this->_version . '/addon/' . $this->_name . '.min.js';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}