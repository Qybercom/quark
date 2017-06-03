<?php
namespace Quark\ViewResources\IonIcons;

use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkForeignViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkDTO;
use Quark\QuarkCSSViewResourceType;

/**
 * Class IonIcons
 *
 * @package Quark\ViewResources\IonIcons
 */
class IonIcons implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource {
	const CURRENT_VERSION = '2.0.1';

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
		return '//code.ionicframework.com/ionicons/' . $this->_version . '/css/ionicons.min.css';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}