<?php
namespace Quark\ViewResources\MediumEditor;

use Quark\IQuarkForeignViewResource;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkCSSViewResourceType;
use Quark\QuarkDTO;

/**
 * Class MediumEditorCSS
 *
 * @package Quark\ViewResources\MediumEditor
 */
class MediumEditorCSS implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource {
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
		return new QuarkCSSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return 'https://cdnjs.cloudflare.com/ajax/libs/medium-editor/' . $this->_version . '/css/medium-editor.min.css';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}