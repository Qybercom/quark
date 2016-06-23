<?php
namespace Quark\ViewResources\jQuery\Plugins\ImagePicker;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkViewResourceWithDependencies;
use Quark\IQuarkForeignViewResource;

use Quark\QuarkDTO;
use Quark\QuarkJSViewResourceType;

use Quark\ViewResources\jQuery\jQueryCore;

/**
 * Class ImagePickerJS
 *
 * @package Quark\ViewResources\jQuery\Plugins\ImagePicker
 */
class ImagePickerJS implements IQuarkViewResource, IQuarkViewResourceWithDependencies, IQuarkForeignViewResource {
	/**
	 * @var string $_version = ImagePicker::CURRENT_VERSION
	 */
	private $_version = ImagePicker::CURRENT_VERSION;

	/**
	 * @param string $version = ImagePicker::CURRENT_VERSION
	 */
	public function __construct ($version = ImagePicker::CURRENT_VERSION) {
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
		return 'https://cdnjs.cloudflare.com/ajax/libs/image-picker/' . $this->_version . '/image-picker.min.js';
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