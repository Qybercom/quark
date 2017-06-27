<?php
namespace Quark\Extensions\PackageManager;

use Quark\IQuarkExtension;

/**
 * Class PackageManager
 *
 * @package Quark\Extensions\PackageManager
 */
class PackageManager implements IQuarkExtension {
	/**
	 * @var IQuarkPackageManagerProvider $_provider
	 */
	private $_provider;
	
	/**
	 * @param IQuarkPackageManagerProvider $provider = null
	 */
	public function __construct (IQuarkPackageManagerProvider $provider = null) {
		$this->Provider($provider);
	}
	
	/**
	 * @param IQuarkPackageManagerProvider $provider = null
	 *
	 * @return IQuarkPackageManagerProvider
	 */
	public function &Provider (IQuarkPackageManagerProvider $provider = null) {
		if (func_num_args() != 0)
			$this->_provider = $provider;
		
		return $this->_provider;
	}
	
	/**
	 * @param PackageManagerPackage $package = null
	 *
	 * @return PackageManagerPackage
	 */
	public function Create (PackageManagerPackage $package = null) {
		return $package == null ? null : $this->_provider->PackageManagerPackageCreate($package);
	}
	
	/**
	 * @param string $location = ''
	 *
	 * @return PackageManagerPackage
	 */
	public function Retrieve ($location = '') {
		return $this->_provider->PackageManagerPackageParse(new PackageManagerPackage($location, true));
	}
}