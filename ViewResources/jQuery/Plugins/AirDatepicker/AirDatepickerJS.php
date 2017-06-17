<?php
namespace Quark\ViewResources\jQuery\Plugins\AirDatepicker;

use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkViewResourceWithDependencies;
use Quark\IQuarkForeignViewResource;

use Quark\QuarkDTO;
use Quark\QuarkJSViewResourceType;

use Quark\ViewResources\jQuery\jQueryCore;

/**
 * Class AirDatepickerJS
 *
 * @package Quark\ViewResources\jQuery\Plugins\AirDatepicker
 */
class AirDatepickerJS implements IQuarkSpecifiedViewResource, IQuarkViewResourceWithDependencies, IQuarkForeignViewResource {
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
		return new QuarkJSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return 'https://cdnjs.cloudflare.com/ajax/libs/air-datepicker/' . $this->_version . '/js/datepicker.min.js';
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