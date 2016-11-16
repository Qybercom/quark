<?php
namespace Quark\ViewResources\Trumbowyg;

use Quark\IQuarkForeignViewResource;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkDTO;
use Quark\QuarkJSViewResourceType;

/**
 * Class TrumbowygJS
 *
 * @package Quark\ViewResources\Trumbowyg
 */
class TrumbowygJS implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource {
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
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}