<?php
namespace Quark\ViewResources\MomentJS;

use Quark\IQuarkViewResource;
use Quark\IQuarkForeignViewResource;

use Quark\IQuarkViewResourceType;

use Quark\QuarkDTO;
use Quark\QuarkJSViewResourceType;

/**
 * Class MomentJS
 *
 * @package Quark\ViewResources\MomentJS
 */
class MomentJS implements IQuarkViewResource, IQuarkForeignViewResource {
	private $_version = '';
	private $_locales = true;

	/**
	 * @param string $version = '2.10.3'
	 * @param bool $locales = true
	 */
	public function __construct ($version = '2.10.3', $locales = true) {
		$this->_version = $version;
		$this->_locales = $locales;
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
		return 'https://cdnjs.cloudflare.com/ajax/libs/moment.js/' . $this->_version . '/moment' . ($this->_locales ? '-with-locales' : '') . '.min.js';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}