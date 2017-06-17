<?php
namespace Quark\ViewResources\Trumbowyg;

use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkViewResourceWithDependencies;
use Quark\IQuarkForeignViewResource;

use Quark\QuarkDTO;
use Quark\QuarkJSViewResourceType;

use Quark\ViewResources\jQuery\jQueryCore;

/**
 * Class TrumbowygJS
 *
 * @package Quark\ViewResources\Trumbowyg
 */
class TrumbowygJS implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource, IQuarkViewResourceWithDependencies {
	/**
	 * @var string $_version = Trumbowyg::CURRENT_VERSION
	 */
	private $_version = Trumbowyg::CURRENT_VERSION;

	/**
	 * @param string $version = Trumbowyg::CURRENT_VERSION
	 */
	public function __construct ($version = Trumbowyg::CURRENT_VERSION) {
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
		return 'https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/' . $this->_version . '/trumbowyg.min.js';
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