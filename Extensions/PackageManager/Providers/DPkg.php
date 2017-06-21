<?php
namespace Quark\Extensions\PackageManager\Providers;

use Quark\QuarkArchive;

use Quark\Extensions\Quark\Archives\ARArchive;

use Quark\Extensions\PackageManager\IQuarkPackageManagerProvider;
use Quark\Extensions\PackageManager\PackageManagerPackage;
use Quark\QuarkArchiveItem;

/**
 * Class DPkg
 *
 * @package Quark\Extensions\PackageManager\Providers
 */
class DPkg implements IQuarkPackageManagerProvider {
	const MAIN_DEBIAN_BINARY = 'debian-binary';
	const MAIN_TAR_CONTROL = 'control.tar.gz';
	const MAIN_TAR_DATA = 'data.tar.gz';
	
	/**
	 * @param PackageManagerPackage $package
	 *
	 * @return PackageManagerPackage
	 */
	public function PackageManagerPackageCreate (PackageManagerPackage $package) {
		$deb = new QuarkArchive(new ARArchive());
		
		$deb[] = new QuarkArchiveItem(self::MAIN_DEBIAN_BINARY, "2.0\n");
		
		$package->Content($deb->Pack()->Content());
		return $package;
	}
	
	/**
	 * @param PackageManagerPackage $package
	 *
	 * @return PackageManagerPackage
	 */
	public function PackageManagerPackageParse (PackageManagerPackage $package) {
		// TODO: Implement PackageManagerPackageParse() method.
	}
}