<?php
namespace Quark\Extensions\PackageManager;

use Quark\QuarkFile;
use Quark\QuarkObject;

/**
 * Class PackageManagerPackage
 *
 * @property string $packageName = ''
 * @property string $packageVersion = ''
 * @property string $packageMaintainer = ''
 * @property string $packageArchitecture = ''
 * @property string[] $packageCategories = []
 * @property string $packageDescription = ''
 * @property string $packageDescriptionLong = ''
 * @property string[] $packageDependencies = []
 * @property string $packageOrigin = ''
 * @property string $packagePriority = ''
 *
 * @package Quark\Extensions\PackageManager
 */
class PackageManagerPackage extends QuarkFile {
	/**
	 * @var QuarkFile[] $_files = []
	 */
	private $_files = array();
	
	/**
	 * @param string $location = ''
	 * @param bool $load = false
	 */
	public function __construct ($location = '', $load = false) {
		parent::__construct($location, $load);
	}
	
	/**
	 * @param QuarkFile[] $files = []
	 *
	 * @return QuarkFile[]
	 */
	public function &Files ($files = []) {
		if (func_num_args() != 0 && QuarkObject::IsArrayOf($files, new QuarkFile()))
			$this->_files = $files;
		
		return $this->_files;
	}

	/**
	 * @return array
	 */
	public function Fields () {
		$fields = parent::Fields();
		
		$fields['packageName'] = '';
		$fields['packageVersion'] = '';
		$fields['packageMaintainer'] = '';
		$fields['packageArchitecture'] = '';
		$fields['packageCategories'] = array();
		$fields['packageDescription'] = '';
		$fields['packageDescriptionLong'] = '';
		$fields['packageDependencies'] = array();
		$fields['packageOrigin'] = '';
		$fields['packagePriority'] = '';
		
		return $fields;
	}
}