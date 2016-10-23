<?php
namespace Quark\ViewResources\TwitterBootstrap\Plugins;

use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkCSSViewResourceType;

/**
 * Class TBSHoverDropdown
 *
 * @package Quark\ViewResources\TwitterBootstrap\Plugins
 */
class TBSHoverDropdown implements IQuarkSpecifiedViewResource {
	private $_version = '';

	/**
	 * @param string $version = '2.0.10'
	 */
	public function __construct ($version = '2.0.10') {
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
		return 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-hover-dropdown/' . $this->_version . '/bootstrap-hover-dropdown.min.js';
	}
}