<?php
namespace Quark\ViewResources\MediumEditor;

use Quark\IQuarkForeignViewResource;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkCSSViewResourceType;
use Quark\QuarkDTO;

/**
 * Class MediumEditorTheme
 *
 * @package Quark\ViewResources\MediumEditor
 */
class MediumEditorTheme implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource {
	const NAME_BEAGLE = 'beagle';
	const NAME_BOOTSTRAP = 'bootstrap';
	const NAME_DEFAULT = 'default';
	const NAME_FLAT = 'flat';
	const NAME_MANI = 'mani';
	const NAME_ROMAN = 'roman';
	const NAME_TIM = 'time';

	/**
	 * @var string $_name = self::NAME_DEFAULT
	 */
	private $_name = self::NAME_DEFAULT;

	/**
	 * @var string $_version = MediumEditor::CURRENT_VERSION
	 */
	private $_version = MediumEditor::CURRENT_VERSION;

	/**
	 * @param string $name = self::NAME_DEFAULT
	 * @param string $version = MediumEditor::CURRENT_VERSION
	 */
	public function __construct ($name = self::NAME_DEFAULT, $version = MediumEditor::CURRENT_VERSION) {
		$this->_name = $name;
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
		return 'https://cdnjs.cloudflare.com/ajax/libs/medium-editor/' . $this->_version . '/css/themes/' . $this->_name . '.min.css';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}