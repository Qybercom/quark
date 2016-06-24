<?php
namespace Quark\ViewResources\jQuery\Plugins\ImagePicker;

use Quark\IQuarkForeignViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkCSSViewResourceType;
use Quark\QuarkDTO;

/**
 * Class ImagePickerCSS
 *
 * @package Quark\ViewResources\jQuery\Plugins\ImagePicker
 */
class ImagePickerCSS implements IQuarkViewResource, IQuarkForeignViewResource {
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
		return new QuarkCSSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return 'https://cdnjs.cloudflare.com/ajax/libs/image-picker/' . $this->_version . '/image-picker.min.css';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}