<?php
namespace Quark\Extensions\PushNotification\Providers\OneSignal;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkLocalizedString;

use Quark\Extensions\PushNotification\Device;
use Quark\Extensions\PushNotification\IQuarkPushNotificationDetails;
use Quark\Extensions\PushNotification\IQuarkPushNotificationProvider;

/**
 * Class OneSignal
 *
 * @package Quark\Extensions\PushNotification\Providers\OneSignal
 */
class OneSignal implements IQuarkPushNotificationProvider {
	const TYPE = 'os';

	const BULK_MAX = 2000;
	const URL = 'https://onesignal.com/api/v1/notifications';

	const OPTION_APP_ID = 'os.id';
	const OPTION_APP_SECRET = 'os.secret';

	/**
	 * @var Device[] $_devices = []
	 */
	private $_devices = array();

	/**
	 * @var IQuarkPushNotificationDetails|OneSignalDetails $_details
	 */
	private $_details;

	/**
	 * @var string $_appID = ''
	 */
	private $_appID = '';

	/**
	 * @var string $_appSecret = ''
	 */
	private $_appSecret = '';

	/**
	 * @return string
	 */
	public function PNPType () {
		return self::TYPE;
	}

	/**
	 * @param $config
	 */
	public function PNPConfig ($config) {
		if (isset($config[self::OPTION_APP_ID]))
			$this->_appID = $config[self::OPTION_APP_ID];

		if (isset($config[self::OPTION_APP_SECRET]))
			$this->_appSecret = $config[self::OPTION_APP_SECRET];
	}

	/**
	 * @param string $key
	 * @param $value
	 *
	 * @return mixed
	 */
	public function PNPOption ($key, $value) {
		switch ($key) {
			case self::OPTION_APP_ID:
				$this->_appID = $value;
				break;

			case self::OPTION_APP_SECRET:
				$this->_appSecret = $value;
				break;

			default: break;
		}
	}

	/**
	 * @param Device $device
	 *
	 * @return bool
	 */
	public function PNPDevice (Device &$device) {
		if ($device->type != self::TYPE) return false;

		$this->_devices[] = $device;

		return true;
	}

	/**
	 * @return Device[]
	 */
	public function &PNPDevices () {
		return $this->_devices;
	}

	/**
	 * @param IQuarkPushNotificationDetails $details
	 *
	 * @return mixed
	 */
	public function PNPDetails (IQuarkPushNotificationDetails $details) {
		$this->_details = $details;
	}

	/**
	 * @param object|array $payload
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function PNPSend ($payload, $options) {
		$devices = array();
		foreach ($this->_devices as $key => &$device)
			$devices[$key] = $device->id;

		$size = sizeof($devices);
		if ($size == 0) return true;

		$i = 0;
		$queues = ceil($size / self::BULK_MAX);

		$data = null;
		$data_headings = null;
		$data_contents = null;
		$request = null;
		$response = null;
		$out = null;

		while ($i < $queues) {
			$data_headings = $this->_details->Headings();
			$data_contents = $this->_details->Contents();

			$data = array(
				'app_id' => $this->_appID,
				'include_player_ids' => array_slice($devices, $i * self::BULK_MAX, self::BULK_MAX),
			);

			if ($data_headings != null)
				$data['headings'] = $this->_localizedString($data_headings);

			if ($data_contents != null)
				$data['contents'] = $this->_localizedString($data_contents);

			$request = QuarkDTO::ForPOST(new QuarkJSONIOProcessor());

			if ($this->_appSecret != '')
				$request->Header(QuarkDTO::HEADER_AUTHORIZATION, 'Basic ' . $this->_appSecret);

			$request->Data($data);

			$response = new QuarkDTO(new QuarkJSONIOProcessor());

			$out = QuarkHTTPClient::To(self::URL, $request, $response);

			if (!$out || isset($out->errors)) {
				Quark::Log('[OneSignal] Error during sending push notification. OneSignal response: ' . print_r($out, true), Quark::LOG_WARN);

				return false;
			}

			$i++;
		}

		unset($data, $request, $response, $out);

		return true;
	}

	/**
	 * @return mixed
	 */
	public function PNPReset () {
		$this->_devices = array();
	}

	/**
	 * @param QuarkLocalizedString $source = null
	 *
	 * @return object
	 */
	private function _localizedString (QuarkLocalizedString &$source = null) {
		$out = clone $source->values;

		if (isset($out->{'*'})) {
			if (!isset($out->en))
				$out->en = $out->{'*'};

			unset($out->{'*'});
		}

		return $out;
	}
}