<?php
namespace Quark\Extensions\PushNotification;

use Quark\IQuarkExtensionConfig;

/**
 * Class Config
 *
 * @package Quark\Extensions\PushNotification
 */
class Config implements IQuarkExtensionConfig {
	/**
	 * @return string
	 */
	public function AssignedExtension () {
		return 'Quark\Extensions\PushNotification\PushNotification';
	}
}