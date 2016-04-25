<?php
namespace Quark\ViewResources\AdminLTE;

use Quark\IQuarkViewResource;
use Quark\IQuarkForeignViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkDTO;
use Quark\QuarkJSViewResourceType;

/**
 * Class AdminLTEApp
 *
 * @package Quark\ViewResources\AdminLTE
 */
class AdminLTEApp implements IQuarkViewResource, IQuarkForeignViewResource {
	/**
	 * @var string $_version = AdminLTE::CURRENT_VERSION
	 */
	private $_version = AdminLTE::CURRENT_VERSION;

	/**
	 * @param string $version = AdminLTE::CURRENT_VERSION
	 */
	public function __construct ($version = AdminLTE::CURRENT_VERSION) {
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
		return 'https://cdnjs.cloudflare.com/ajax/libs/admin-lte/' . $this->_version . '/js/app.min.js';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}