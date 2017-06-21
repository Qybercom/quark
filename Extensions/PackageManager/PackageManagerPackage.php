<?php
namespace Quark\Extensions\PackageManager;

use Quark\QuarkFile;
use Quark\QuarkObject;

/**
 * Class PackageManagerPackage
 *
 * @package Quark\Extensions\PackageManager
 */
class PackageManagerPackage extends QuarkFile {
	/**
	 * @var string $_packageName = ''
	 */
	private $_packageName = '';
	
	/**
	 * @var string $_packageVersion = ''
	 */
	private $_packageVersion = '';
	
	/**
	 * @var string $_packageMaintainer = ''
	 */
	private $_packageMaintainer = '';
	
	/**
	 * @var string $_packageArchitecture = ''
	 */
	private $_packageArchitecture = '';
	
	/**
	 * @var array $_packageCategories = ''
	 */
	private $_packageCategories = array();
	
	/**
	 * @var string $_packageDescription = ''
	 */
	private $_packageDescription = '';
	
	/**
	 * @var string[] $_packageDependencies = []
	 */
	private $_packageDependencies = array();
	
	/**
	 * @var string $_packageOrigin = ''
	 */
	private $_packageOrigin = '';
	
	/**
	 * @var string $_packagePriority = ''
	 */
	private $_packagePriority = '';
	
	/**
	 * @var QuarkFile[] $_files = []
	 */
	private $_files;
	
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
		if (func_num_args() != 0 && QuarkObject::IsArrayOf($files, 'Quark\\QuarkFile'))
			$this->_files = $files;
		
		return $this->_files;
	}
}