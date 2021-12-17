<?php
namespace Quark\Extensions\PushNotification\Providers\OneSignal;

use Quark\QuarkLocalizedString;

use Quark\Extensions\PushNotification\IQuarkPushNotificationDetails;
use Quark\Extensions\PushNotification\IQuarkPushNotificationDevice;
use Quark\Extensions\PushNotification\PushNotificationDetails;

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

	/**
	 * @param object|array $payload
	 * @param IQuarkPushNotificationDevice $device = null
	 *
	 * @return mixed
	 */
	public function PushNotificationDetailsData ($payload, IQuarkPushNotificationDevice $device = null) {
		$headings = $this->LocalizedString($this->Headings());
		if ($headings != null) $payload['headings'] = $headings;

		$contents = $this->LocalizedString($this->Contents());
		if ($contents != null) $payload['contents'] = $contents;

		return $payload;
	}

	/**
	 * @param PushNotificationDetails $details
	 *
	 * @return mixed
	 */
	public function PushNotificationDetailsFromDetails (PushNotificationDetails $details) {
		$this->Headings(new QuarkLocalizedString($details->Title()));
		$this->Contents(new QuarkLocalizedString($details->Body()));
	}

	/**
	 * @param QuarkLocalizedString $source = null
	 *
	 * @return object
	 */
	public function LocalizedString (QuarkLocalizedString $source = null) {
		$out = clone $source->values;

		if (isset($out->{'*'})) {
			if (!isset($out->en))
				$out->en = $out->{'*'};

			unset($out->{'*'});
		}

		return $out;
	}
}