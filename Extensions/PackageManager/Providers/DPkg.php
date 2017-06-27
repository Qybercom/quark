<?php
namespace Quark\Extensions\PackageManager\Providers;

use Quark\QuarkArchive;
use Quark\QuarkArchiveItem;

use Quark\Extensions\Quark\Archives\ARArchive;
use Quark\Extensions\Quark\Archives\TARArchive;

use Quark\Extensions\Quark\Compressors\GZIPCompressor;

use Quark\Extensions\PackageManager\IQuarkPackageManagerProvider;
use Quark\Extensions\PackageManager\PackageManagerPackage;
use Quark\QuarkModel;

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
		$package = (new QuarkModel(new PackageManagerPackage(), $package))->Model();
		
		$deb = new QuarkArchive(new ARArchive());
		
		$deb[] = new QuarkArchiveItem(self::MAIN_DEBIAN_BINARY, "2.0\n");
		
		$control = new QuarkArchive(new TARArchive(new GZIPCompressor()));
		$control[] = new QuarkArchiveItem('control', $this->PackageControl($package));
		
		$files = $package->Files();
		$md5sums = '';
		$data = new QuarkArchive(new TARArchive(new GZIPCompressor()));
		
		foreach ($files as $i => &$file) {
			$md5sums .= md5($file->Content()) . '  ' . $file->location . "\n";
			$data[] = new QuarkArchiveItem($file->location, $file->Content());
		}
		
		$control[] = new QuarkArchiveItem('md5sums', $md5sums);
		
		$deb[] = new QuarkArchiveItem(self::MAIN_TAR_CONTROL, $control->Pack()->Content());
		
		$deb[] = new QuarkArchiveItem(self::MAIN_TAR_DATA, $data->Pack()->Content());
		
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
	
	/**
	 * @param PackageManagerPackage $package = ''
	 *
	 * @return string
	 */
	public function PackageControl (PackageManagerPackage $package = null) {
		return $package == null
			? ''
			: 'Package: ' . $package->packageName . "\n"
			. 'Version: ' . $package->packageVersion . "\n"
			. 'Maintainer: ' . $package->packageMaintainer . "\n"
			. 'Architecture: ' . $package->packageArchitecture . "\n"
			. 'Section: ' . implode(' ', $package->packageCategories) . "\n"
			. 'Description: ' . $package->packageDescription . "\n"
			. (strlen($package->packageDescriptionLong) == 0 ? '' : ' ' . str_replace("\n", ".", $package->packageDescriptionLong) . "\n")
			. 'Depends: ' . implode(' ', $package->packageDependencies) . "\n"
			. 'Origin: ' . $package->packageOrigin . "\n"
			. 'Priority: ' . $package->packagePriority . "\n";
	}
}