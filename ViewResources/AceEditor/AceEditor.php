<?php
namespace Quark\ViewResources\AceEditor;

use Quark\IQuarkForeignViewResource;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkDTO;
use Quark\QuarkJSViewResourceType;

/**
 * Class AceEditor
 *
 * @package Quark\ViewResources\AceEditor
 */
class AceEditor implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource {
	const CURRENT_VERSION = '1.40.0';//36.2';
	
	/**
	 * @var string $_version = self::CURRENT_VERSION
	 */
	private $_version = self::CURRENT_VERSION;
	
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
		return 'https://cdnjs.cloudflare.com/ajax/libs/ace/' . $this->_version . '/ace.min.js';
	}
	
	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}