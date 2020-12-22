<?php
namespace Quark\Extensions\GoogleFirebase;

/**
 * Interface IQuarkGoogleFirebaseLibrary
 *
 * @package Quark\Extensions\GoogleFirebase
 */
interface IQuarkGoogleFirebaseLibrary {
	/**
	 * @param GoogleFirebase $firebase
	 *
	 * @return string
	 */
	public function GoogleFirebaseLibraryURL(GoogleFirebase &$firebase);
}