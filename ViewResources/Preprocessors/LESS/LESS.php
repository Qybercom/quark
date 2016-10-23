<?php
namespace Quark\ViewResources\Preprocessors\LESS;

use Quark\IQuarkInlineViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceWithBackwardDependencies;

use Quark\Quark;
use Quark\QuarkSource;

/**
 * Class LESS
 *
 * @package Quark\ViewResources\Preprocessors\LESS
 */
class LESS implements IQuarkViewResource, IQuarkInlineViewResource, IQuarkViewResourceWithBackwardDependencies {
	/**
	 * @var string $_location
	 */
	private $_location = '';

	/**
	 * @var string $_version = LESSClientCompiler::CURRENT_VERSION
	 */
	private $_version = LESSClientCompiler::CURRENT_VERSION;

	/**
	 * @var bool $_client = false
	 */
	private $_client = false;

	/**
	 * @var bool $_obfuscate = true
	 */
	private $_obfuscate = true;

	/**
	 * @param string $location
	 * @param bool $obfuscate = true
	 */
	public function __construct ($location, $obfuscate = true) {
		$this->_location = $location;
		$this->_obfuscate = $obfuscate;
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function BackwardDependencies () {
		return array(
			$this->_client ? new LESSClientCompiler($this->_version) : null
		);
	}

	/**
	 * @return string
	 */
	public function HTML () {
		if ($this->_client)
			return '<link rel="stylesheet/less" type="text/css" href="' . Quark::WebLocation($this->_location) . '" />';

		$less = new QuarkSource($this->_location, true);

		if ($this->_obfuscate)
			$less->Obfuscate();

		return '<style type="text/css">' . $less->Content() . '</style>';
	}

	/**
	 * @param string $location
	 * @param string $version = LESSClientCompiler::CURRENT_VERSION
	 *
	 * @return LESS
	 */
	public static function ClientCompiler ($location, $version = LESSClientCompiler::CURRENT_VERSION) {
		$less = new self($location);
		$less->_version = $version;
		$less->_client = true;

		return $less;
	}
}