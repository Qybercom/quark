<?php
namespace Quark\ViewResources\FontAwesome;

use Quark\IQuarkViewResource;
use Quark\IQuarkForeignViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkDTO;
use Quark\QuarkCSSViewResourceType;

/**
 * Class FontAwesome
 *
 * @package Quark\ViewResources\FontAwesome
 */
class FontAwesome implements IQuarkViewResource, IQuarkForeignViewResource {
	const CURRENT_VERSION = '4.6.2';

	/**
	 * @var string $_version = self::CURRENT_VERSION
	 */
	private $_version = self::CURRENT_VERSION;

	/**
	 * @param string $version = self::CURRENT_VERSION
	 */
	public function __construct ($version = self::CURRENT_VERSION) {
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
		return '//maxcdn.bootstrapcdn.com/font-awesome/' . $this->_version . '/css/font-awesome.min.css';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}