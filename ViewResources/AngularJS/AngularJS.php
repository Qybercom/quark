<?php
namespace Quark\ViewResources\AngularJS;

use Quark\IQuarkForeignViewResource;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\QuarkDTO;
use Quark\QuarkJSViewResourceType;

/**
 * Class AngularJS
 *
 * @package Quark\ViewResources\AngularJS
 */
class AngularJS implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource, IQuarkViewResourceWithDependencies {
	const VERSION_CURRENT = '11.1.1';

	const MODULE_ANIMATIONS = 'animations';
	const MODULE_COMMON = 'common';
	const MODULE_COMPILER = 'compiler';
	const MODULE_CORE = 'core';
	const MODULE_FORMS = 'forms';
	const MODULE_PLATFORM_BROWSER = 'platform-browser';
	const MODULE_PLATFORM_BROWSER_DYNAMIC = 'platform-browser-dynamic';
	const MODULE_ROUTER = 'router';

	/**
	 * @var string $_version = self::VERSION_CURRENT
	 */
	private $_version = self::VERSION_CURRENT;

	/**
	 * @var string[] $_modules = []
	 */
	private $_modules = array();

	/**
	 * @param string $version = self::VERSION_CURRENT
	 * @param string[] $modules = []
	 */
	public function __construct ($version = self::VERSION_CURRENT, $modules = []) {
		if (func_num_args() < 1)
			$modules = array(
				self::MODULE_FORMS,
				self::MODULE_PLATFORM_BROWSER
			);

		$this->Version($version);
		$this->Modules($modules);
	}

	/**
	 * @param string $version = self::VERSION_CURRENT
	 *
	 * @return string
	 */
	public function Version ($version = self::VERSION_CURRENT) {
		if (func_num_args() != 0)
			$this->_version = $version;

		return $this->_version;
	}

	/**
	 * @param string[] $modules = []
	 *
	 * @return string[]
	 */
	public function Modules ($modules = []) {
		if (func_num_args() != 0)
			$this->_modules = $modules;

		return $this->_modules;
	}

	/**
	 * @param string $module = ''
	 *
	 * @return $this
	 */
	public function Module ($module = '') {
		if (func_num_args() != 0)
			$this->_modules[] = $module;

		return $this;
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
		return self::ModuleURL(self::MODULE_CORE, $this->_version);
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
		$out = array();

		foreach ($this->_modules as $module)
			$out[] = self::ModuleURL($module, $this->_version);

		return $out;
	}

	/**
	 * @param string $name = ''
	 * @param string $version = self::VERSION_CURRENT
	 *
	 * @return string
	 */
	public static function ModuleURL ($name = '', $version = self::VERSION_CURRENT) {
		return 'https://cdn.jsdelivr.net/npm/@angular/' . $name . '@' . $version . '/bundles/' . $name . '.umd.min.js';
	}
}