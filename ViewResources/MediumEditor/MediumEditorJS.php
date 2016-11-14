<?php
namespace Quark\ViewResources\MediumEditor;

use Quark\IQuarkForeignViewResource;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkJSViewResourceType;
use Quark\QuarkDTO;

/**
 * Class MediumEditorJS
 *
 * @package Quark\ViewResources\MediumEditor
 */
class MediumEditorJS implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource {
	/**
	 * @var string $_version = MediumEditor::CURRENT_VERSION
	 */
	private $_version = MediumEditor::CURRENT_VERSION;

	/**
	 * @param string $version = MediumEditor::CURRENT_VERSION
	 */
	public function __construct ($version = MediumEditor::CURRENT_VERSION) {
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
		return 'https://cdnjs.cloudflare.com/ajax/libs/medium-editor/' . $this->_version . '/js/medium-editor.min.js';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}