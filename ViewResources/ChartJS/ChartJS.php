<?php
namespace Quark\ViewResources\ChartJS;

use Quark\IQuarkForeignViewResource;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\IQuarkViewResourceType;

use Quark\QuarkDTO;
use Quark\QuarkJSViewResourceType;

use Quark\ViewResources\jQuery\jQueryCore;

/**
 * Class ChartJS
 *
 * @package Quark\ViewResources\ChartJS
 */
class ChartJS implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource, IQuarkViewResourceWithDependencies {
	const VERSION_1_0_2 = '1.0.2';
	const VERSION_2_6_0 = '2.6.0';
	const VERSION_3_7_1 = '3.7.1';
	const VERSION_3_9_1 = '3.9.1';
	const VERSION_4_4_8 = '4.4.8';

	const CURRENT_VERSION = self::VERSION_4_4_8;

	/**
	 * @var string $_version = self::CURRENT_VERSION
	 */
	private $_version = self::CURRENT_VERSION;

	/**
	 * @param string $version = self::CURRENT_VERSION
	 */
	public function __construct ($version = self::CURRENT_VERSION) {
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
		//return 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/' . $this->_version . '/Chart.min.js';
		//return 'https://cdn.jsdelivr.net/npm/chart.js@' . $this->_version . '/dist/chart.min.js';
		return 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/' . $this->_version . '/chart' . (preg_match('#^4\.\d+\.\d+(-[a-zA-Z0-9\-.]+)?$#is', $this->_version) ? '.umd' : '') . '.min.js';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			new jQueryCore()
		);
	}
}