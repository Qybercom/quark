<?php
namespace Quark\ViewResources\AdminLTE;

use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkForeignViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkDTO;
use Quark\QuarkCSSViewResourceType;

/**
 * Class AdminLTESkin
 *
 * @package Quark\ViewResources\AdminLTE
 */
class AdminLTESkin implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource {
	const ALL = '_all-skins';
	const BLUE = 'skin-blue';
	const BLUE_LIGHT = 'skin-blue-light';
	const BLACK = 'skin-black';
	const BLACK_LIGHT = 'skin-black-light';
	const PURPLE = 'skin-purple';
	const PURPLE_LIGHT = 'skin-purple-light';
	const YELLOW = 'skin-yellow';
	const YELLOW_LIGHT = 'skin-yellow-light';
	const RED = 'skin-red';
	const RED_LIGHT = 'skin-red-light';
	const GREEN = 'skin-green';
	const GREEN_LIGHT = 'skin-green-light';

	/**
	 * @var string $_version = AdminLTE::CURRENT_VERSION
	 */
	private $_version = AdminLTE::CURRENT_VERSION;

	/**
	 * @var string $_name = self::ALL
	 */
	private $_name = self::ALL;

	/**
	 * @param string $name = self::ALL
	 * @param string $version = AdminLTE::CURRENT_VERSION
	 */
	public function __construct ($name = self::ALL, $version = AdminLTE::CURRENT_VERSION) {
		$this->_name = $name;
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
		return 'https://cdnjs.cloudflare.com/ajax/libs/admin-lte/' . $this->_version . '/css/skins/' . $this->_name . '.min.css';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}