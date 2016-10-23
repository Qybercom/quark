<?php
namespace Quark\ViewResources\Materialize;

use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkViewResourceWithDependencies;
use Quark\IQuarkForeignViewResource;

use Quark\QuarkCSSViewResourceType;
use Quark\QuarkDTO;

use Quark\ViewResources\Google\GoogleFont;

/**
 * Class MaterializeCSS
 *
 * @package Quark\ViewResources\Materialize
 */
class MaterializeCSS implements IQuarkSpecifiedViewResource, IQuarkViewResourceWithDependencies, IQuarkForeignViewResource {
	/**
	 * @var string $_version = Materialize::CURRENT_VERSION
	 */
	private $_version = Materialize::CURRENT_VERSION;

	/**
	 * @param string $version = Materialize::CURRENT_VERSION
	 */
	public function __construct ($version = Materialize::CURRENT_VERSION) {
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
		return 'https://cdnjs.cloudflare.com/ajax/libs/materialize/' . $this->_version . '/css/materialize.min.css';
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			new GoogleFont('Roboto', array(GoogleFont::OPTION_SIZES => array(
				GoogleFont::N300,
				GoogleFont::N600,
				GoogleFont::N800
			))),
			new GoogleFont('Material Icons')
		);
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}