<?php
namespace Quark\Extensions\GoogleFirebase\Libraries;

use Quark\Extensions\GoogleFirebase\IQuarkGoogleFirebaseLibrary;

use Quark\Extensions\GoogleFirebase\GoogleFirebase;

/**
 * Class GoogleFirebaseAnalytics
 *
 * @package Quark\Extensions\GoogleFirebase\Libraries
 */
class GoogleFirebaseAnalytics implements IQuarkGoogleFirebaseLibrary {
	const LIB_NAME = 'firebase-analytics';

	/**
	 * @param GoogleFirebase $firebase
	 *
	 * @return string
	 */
	public function GoogleFirebaseLibraryURL (GoogleFirebase &$firebase) {
		return $firebase->LibraryJS(self::LIB_NAME);
	}
}