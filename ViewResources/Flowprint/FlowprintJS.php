<?php
namespace Quark\ViewResources\Flowprint;

use Quark\IQuarkForeignViewResource;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkDTO;
use Quark\QuarkJSViewResourceType;
use Quark\QuarkMinimizableViewResourceBehavior;

/**
 * Class FlowprintJS
 *
 * @package Quark\ViewResources\Flowprint
 */
class FlowprintJS implements IQuarkViewResource, IQuarkSpecifiedViewResource, IQuarkForeignViewResource {
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
		return new QuarkJSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return 'https://cdn.jsdelivr.net/gh/Qybercom/Flowprint@' . $this->_version . '/src/flowprint.js';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}