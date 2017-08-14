<?php
namespace Quark\ViewResources\Zocial;

use Quark\IQuarkForeignViewResource;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkDTO;
use Quark\QuarkCSSViewResourceType;

/**
 * Class ZocialLib
 *
 * @package Quark\ViewResources\Zocial
 */
class ZocialLib implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource {
	/**
	 * @var string $_version = Zocial::VERSION
	 */
	private $_version = Zocial::VERSION;

	/**
	 * @param string $version = Zocial::VERSION
	 */
	public function __construct ($version = Zocial::VERSION) {
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
		return 'https://cdnjs.cloudflare.com/ajax/libs/css-social-buttons/' . $this->_version . '/css/zocial.min.css';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}