<?php
namespace Quark\ViewResources\Materialize;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkViewResourceWithDependencies;
use Quark\IQuarkForeignViewResource;

use Quark\QuarkJSViewResourceType;
use Quark\QuarkDTO;

use Quark\ViewResources\jQuery\jQueryCore;

/**
 * Class MaterializeJS
 *
 * @package Quark\ViewResources\Materialize
 */
class MaterializeJS implements IQuarkViewResource, IQuarkViewResourceWithDependencies, IQuarkForeignViewResource {
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
		return new QuarkJSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return 'https://cdnjs.cloudflare.com/ajax/libs/materialize/' . $this->_version . '/js/materialize.min.js';
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