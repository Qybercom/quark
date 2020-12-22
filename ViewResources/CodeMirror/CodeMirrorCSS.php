<?php
namespace Quark\ViewResources\CodeMirror;

use Quark\IQuarkForeignViewResource;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkCSSViewResourceType;
use Quark\QuarkDTO;

/**
 * Class CodeMirrorCSS
 *
 * @package Quark\ViewResources\CodeMirror
 */
class CodeMirrorCSS implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource {
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
		return new QuarkCSSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/' . $this->_version . '/codemirror.min.css';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}