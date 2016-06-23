<?php
namespace Quark\ViewResources\MomentJS;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkForeignViewResource;

use Quark\QuarkDTO;
use Quark\QuarkJSViewResourceType;

/**
 * Class MomentJS
 *
 * @package Quark\ViewResources\MomentJS
 */
class MomentJS implements IQuarkViewResource, IQuarkForeignViewResource {
	const CURRENT_VERSION = '2.13.0';

	/**
	 * @var string $_version = self::CURRENT_VERSION
	 */
	private $_version = self::CURRENT_VERSION;

	/**
	 * @var bool $_locales = true
	 */
	private $_locales = true;

	/**
	 * @param string $version = self::CURRENT_VERSION
	 * @param bool $locales = true
	 */
	public function __construct ($version = self::CURRENT_VERSION, $locales = true) {
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