<?php
namespace Quark\Extensions\PushNotification\Providers\OneSignal;

use Quark\QuarkLocalizedString;

use Quark\Extensions\PushNotification\IQuarkPushNotificationDetails;

/**
 * Class OneSignalDetails
 *
 * @package Quark\Extensions\PushNotification\Providers\OneSignal
 */
class OneSignalDetails implements IQuarkPushNotificationDetails {
	/**
	 * @var QuarkLocalizedString $_headings = null
	 */
	private $_headings;

	/**
	 * @var QuarkLocalizedString $_contents = null
	 */
	private $_contents = null;

	/**
	 * @return string
	 */
	public function PNProviderType () {
		return OneSignal::TYPE;
	}

	/**
	 * @param object|array $payload
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function PNDetails ($payload, $options) {
		// TODO: Implement PNDetails() method.
	}

	/**
	 * @param QuarkLocalizedString $headings =null
	 *
	 * @return QuarkLocalizedString
	 */
	public function Headings (QuarkLocalizedString $headings =null) {
		if (func_num_args() != 0)
			$this->_headings = $headings;

		return $this->_headings;
	}

	/**
	 * @param QuarkLocalizedString $contents = null
	 *
	 * @return QuarkLocalizedString
	 */
	public function Contents (QuarkLocalizedString $contents = null) {
		if (func_num_args() != 0)
			$this->_contents = $contents;

		return $this->_contents;
	}
}