<?php
namespace Quark\Extensions\PushNotification\Providers\OneSignal;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;

use Quark\Extensions\PushNotification\IQuarkPushNotificationProvider;
use Quark\Extensions\PushNotification\IQuarkPushNotificationDetails;
use Quark\Extensions\PushNotification\IQuarkPushNotificationDevice;

use Quark\Extensions\PushNotification\PushNotificationDevice;
use Quark\Extensions\PushNotification\PushNotificationResult;

/**
 * Class OneSignal
 *
 * @package Quark\Extensions\PushNotification\Providers\OneSignal
 */
class OneSignal implements IQuarkPushNotificationProvider {
	const TYPE = 'os';
	const URL_API = 'https://onesignal.com/api/v1/notifications';
	const BULK_MAX = 2000;

	/**
	 * @var PushNotificationDevice[] $_devices = []
	 */
	private $_devices = array();

	/**
	 * @var string $_appID = ''
	 */
	private $_appID = '';

	/**
	 * @var string $_appSecret = ''
	 */
	private $_appSecret = '';

	/**
	 * @param string $appID = ''
	 *
	 * @return string
	 */
	public function AppID ($appID = '') {
		if (func_num_args() != 0)
			$this->_appID = $appID;

		return $this->_appID;
	}

	/**
	 * @param string $appSecret = ''
	 *
	 * @return string
	 */
	public function AppSecret ($appSecret = '') {
		if (func_num_args() != 0)
			$this->_appSecret = $appSecret;

		return $this->_appSecret;
	}

	/**
	 * @return string
	 */
	public function PushNotificationProviderType () {
		return self::TYPE;
	}

	/**
	 * @return string[]
	 */
	public function PushNotificationProviderProperties () {
		return array(
			'AppID',
			'AppSecret'
		);
	}

	/**
	 * @param string $config
	 *
	 * @return mixed
	 */
	public function PushNotificationProviderInit ($config) {
		// TODO: Implement PushNotificationProviderInit() method.
	}

	/**
	 * @return IQuarkPushNotificationDetails
	 */
	public function PushNotificationProviderDetails () {
		return new OneSignalDetails();
	}

	/**
	 * @return IQuarkPushNotificationDevice
	 */
	public function PushNotificationProviderDevice () {
		return new OneSignalDevice();
	}

	/**
	 * @param PushNotificationDevice $device
	 *
	 * @return mixed
	 */
	public function PushNotificationProviderDeviceAdd (PushNotificationDevice &$device) {
		$this->_devices[$device->id] = $device;
	}

	/**
	 * @param IQuarkPushNotificationDetails $details
	 * @param object|array $payload
	 *
	 * @return PushNotificationResult
	 */
	public function PushNotificationProviderSend (IQuarkPushNotificationDetails &$details, $payload) {
		$out = new PushNotificationResult();
		$size = sizeof($this->_devices);

		$rounds = ceil($size / self::BULK_MAX);
		$out->CountRounds($rounds);

		if ($size != 0) {
			$request = null;
			$response = null;
			$devices = array_keys($this->_devices);
			$i = 0;

			while ($i < $rounds) {
				$request = QuarkDTO::ForPOST(new QuarkJSONIOProcessor());
				$request->Data($details->PushNotificationDetailsData(array(
					'app_id' => $this->_appID,
					'include_player_ids' => array_slice($devices, $i * self::BULK_MAX, self::BULK_MAX),
				)));

				if ($this->_appSecret != '')
					$request->Header(QuarkDTO::HEADER_AUTHORIZATION, 'Basic ' . $this->_appSecret);

				$response = QuarkHTTPClient::To(self::URL_API, $request, new QuarkDTO(new QuarkJSONIOProcessor()));

				if (!isset($response->errors)) $out->CountSuccessAppend(1);
				else {
					Quark::Log('[PushNotification:OneSignal] Error during sending push notification. OneSignal response: ' . print_r($response, true), Quark::LOG_WARN);

					$out->CountFailureAppend(1);
				}

				$i++;
			}

			unset($data, $request, $response, $i, $devices);
		}

		unset($rounds, $size);

		return $out;
	}

	/**
	 * @return mixed
	 */
	public function PushNotificationProviderReset () {
		$this->_devices = array();
	}
}