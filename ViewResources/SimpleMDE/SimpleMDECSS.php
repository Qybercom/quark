<?php
namespace Quark\ViewResources\SimpleMDE;

use Quark\IQuarkForeignViewResource;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkCSSViewResourceType;
use Quark\QuarkDTO;

/**
 * Class SimpleMDECSS
 *
 * @package Quark\ViewResources\SimpleMDE
 */
class SimpleMDECSS implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource {
	/**
	 * @var string $_version = SimpleMDE::CURRENT_VERSION
	 */
	private $_version = SimpleMDE::CURRENT_VERSION;

	/**
	 * @param string $version = SimpleMDE::CURRENT_VERSION
	 */
	public function __construct ($version = SimpleMDE::CURRENT_VERSION) {
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
		return 'https://cdnjs.cloudflare.com/ajax/libs/simplemde/' . $this->_version . '/simplemde.min.css';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}