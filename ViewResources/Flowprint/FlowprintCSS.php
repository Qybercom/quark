<?php
namespace Quark\ViewResources\Flowprint;

use Quark\IQuarkForeignViewResource;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkCSSViewResourceType;
use Quark\QuarkDTO;
use Quark\QuarkMinimizableViewResourceBehavior;

/**
 * Class FlowprintCSS
 *
 * @package Quark\ViewResources\Flowprint
 */
class FlowprintCSS implements IQuarkViewResource, IQuarkSpecifiedViewResource, IQuarkForeignViewResource {
	use QuarkMinimizableViewResourceBehavior;

	/**
	 * @var string $_version = Flowprint::CURRENT_VERSION
	 */
	private $_version = Flowprint::CURRENT_VERSION;

	/**
	 * @param string $version = Flowprint::CURRENT_VERSION
	 */
	public function __construct ($version = Flowprint::CURRENT_VERSION) {
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
		return 'https://cdn.jsdelivr.net/gh/Qybercom/Flowprint@' . $this->_version . '/src/flowprint.css';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}