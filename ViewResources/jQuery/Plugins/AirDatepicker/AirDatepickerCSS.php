<?php
namespace Quark\ViewResources\jQuery\Plugins\AirDatepicker;

use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkForeignViewResource;

use Quark\QuarkDTO;
use Quark\QuarkCSSViewResourceType;

/**
 * Class AirDatepickerCSS
 *
 * @package Quark\ViewResources\jQuery\Plugins\AirDatepicker
 */
class AirDatepickerCSS implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource {
	/**
	 * @var string $_version = AirDatepicker::CURRENT_VERSION
	 */
	private $_version = AirDatepicker::CURRENT_VERSION;

	/**
	 * @param string $version = ImagePicker::CURRENT_VERSION
	 */
	public function __construct ($version = AirDatepicker::CURRENT_VERSION) {
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
		return 'https://cdnjs.cloudflare.com/ajax/libs/air-datepicker/' . $this->_version . '/css/datepicker.min.css';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}