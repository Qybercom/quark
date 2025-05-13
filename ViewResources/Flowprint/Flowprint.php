<?php
namespace Quark\ViewResources\Flowprint;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\QuarkGenericViewResource;

/**
 * Class Flowprint
 *
 * @package Quark\ViewResources\Flowprint
 */
class Flowprint implements IQuarkViewResource, IQuarkViewResourceWithDependencies {
	const VERSION_CURRENT = '1.1.0';
	const VERSION_UNKNOWN = 'unknown';

	/**
	 * @var string $_version = self::VERSION_CURRENT
	 */
	private $_version = self::VERSION_CURRENT;
	
	/**
	 * @var bool $_minified = true
	 */
	private $_minified = true;
	
	/**
	 * @var string $_location = null
	 */
	private $_location = null;

	/**
	 * @param string $version = self::VERSION_CURRENT
	 * @param bool $minified = true
	 */
	public function __construct ($version = self::VERSION_CURRENT, $minified = true) {
		$this->_version = $version;
		$this->_minified = $minified;
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return $this->_location === null
			? array(
				new FlowprintCSS($this->_version, $this->_minified),
				new FlowprintJS($this->_version, $this->_minified)
			)
			: array(
				QuarkGenericViewResource::CSS($this->_location . ($this->_minified ? '/dist/flowprint.min.css' : '/src/flowprint.css')),
				QuarkGenericViewResource::JS($this->_location . ($this->_minified ? '/dist/flowprint.min.js' : '/src/flowprint.js'))
			);
	}
	
	/**
	 * @param string $location = ''
	 * @param bool $minified = false
	 *
	 * @return Flowprint
	 */
	public static function Local ($location = '', $minified = false) {
		$out = new self(self::VERSION_UNKNOWN, $minified);
		$out->_location = $location;
		
		return $out;
	}
}