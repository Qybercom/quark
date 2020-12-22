<?php
namespace Quark\ViewResources\CodeMirror;

use Quark\IQuarkForeignViewResource;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkJSViewResourceType;
use Quark\QuarkDTO;

/**
 * Class CodeMirrorJS
 *
 * @package Quark\ViewResources\CodeMirror
 */
class CodeMirrorJS implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource {
	/**
	 * @var string $_version
	 */
	private $_version = CodeMirror::VERSION_CURRENT;

	/**
	 * @param string $version = CodeMirror::VERSION_CURRENT
	 */
	public function __construct ($version = CodeMirror::VERSION_CURRENT) {
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
		return 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/' . $this->_version . '/codemirror.min.js';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}