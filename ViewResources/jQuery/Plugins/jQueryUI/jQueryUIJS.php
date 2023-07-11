<?php
namespace Quark\ViewResources\jQuery\Plugins\jQueryUI;

use Quark\IQuarkForeignViewResource;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\QuarkDTO;
use Quark\QuarkJSViewResourceType;

use Quark\ViewResources\jQuery\jQueryCore;

/**
 * Class jQueryUIJS
 *
 * @package Quark\ViewResources\jQuery\Plugins\jQueryUI
 */
class jQueryUIJS implements IQuarkSpecifiedViewResource, IQuarkViewResourceWithDependencies, IQuarkForeignViewResource {
	/**
	 * @var string $_version = jQueryUI::CURRENT_VERSION
	 */
	private $_version = jQueryUI::CURRENT_VERSION;

	/**
	 * @param string $version = jQueryUI::CURRENT_VERSION
	 */
	public function __construct ($version = jQueryUI::CURRENT_VERSION) {
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
		return 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/' . $this->_version . '/jquery-ui.min.js';
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			new jQueryCore()
		);
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}