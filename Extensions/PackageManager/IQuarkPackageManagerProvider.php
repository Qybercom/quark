<?php
namespace Quark\Extensions\PackageManager;

/**
 * Interface IQuarkPackageManagerProvider
 *
 * @package Quark\Extensions\PackageManager
 */
interface IQuarkPackageManagerProvider {
	/**
	 * @param PackageManagerPackage $package
	 *
	 * @return PackageManagerPackage
	 */
	public function PackageManagerPackageCreate(PackageManagerPackage $package);
	
	/**
	 * @param PackageManagerPackage $package
	 *
	 * @return PackageManagerPackage
	 */
	public function PackageManagerPackageParse(PackageManagerPackage $package);
}