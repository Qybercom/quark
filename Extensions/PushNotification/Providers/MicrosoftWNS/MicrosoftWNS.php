<?php
namespace Quark\Extensions\PushNotification\Providers\MicrosoftWNS;

use Quark\QuarkDTO;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkPlainIOProcessor;

use Quark\Extensions\PushNotification\IQuarkPushNotificationProvider;
use Quark\Extensions\PushNotification\IQuarkPushNotificationDetails;
use Quark\Extensions\PushNotification\IQuarkPushNotificationDevice;

use Quark\Extensions\PushNotification\PushNotificationResult;
use Quark\Extensions\PushNotification\PushNotificationDevice;

/**
 * Class MicrosoftWNS
 *
 * @package Quark\Extensions\PushNotification\Providers\MicrosoftWNS
 */
class MicrosoftWNS implements IQuarkPushNotificationProvider {
	const TYPE = 'windows';

	const URL_TOKEN = 'https://login.live.com/accesstoken.srf';

	const HEADER_STATUS = 'X-WNS-STATUS';
	const HEADER_NOTIFICATION_STATUS = 'X-WNS-NOTIFICATIONSTATUS';
	const HEADER_MESSAGE_ID = 'X-WNS-MSG-ID';
	const HEADER_DEBUG_TRACE = 'X-WNS-DEBUG-TRACE';
	const HEADER_TYPE = 'X-WNS-Type';

	const STATUS_RECEIVED = 'received';
	const STATUS_DROPPED = 'dropped';

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
		return new MicrosoftWNSDetails();
	}

	/**
	 * @return IQuarkPushNotificationDevice
	 */
	public function PushNotificationProviderDevice () {
		return new MicrosoftWNSDevice();
	}

	/**
	 * @param PushNotificationDevice $device
	 *
	 * @return mixed
	 */
	public function PushNotificationProviderDeviceAdd (PushNotificationDevice &$device) {
		$this->_devices[] = $device;
	}

	/**
	 * @param IQuarkPushNotificationDetails $details
	 * @param object|array $payload
	 *
	 * @return PushNotificationResult
	 */
	public function PushNotificationProviderSend (IQuarkPushNotificationDetails &$details, $payload) {
		$out = new PushNotificationResult();

		$token = $this->Token();

		if ($token != null) {
			$request = null;
			$response = null;

			foreach ($this->_devices as $i => &$device) {
				$request = $details->PushNotificationDetailsData($payload, $device->ToDevice(new MicrosoftWNSDevice()));
				$request->Header(QuarkDTO::HEADER_AUTHORIZATION, 'Bearer ' . $token);

				$response = QuarkHTTPClient::To($device->id, $request, new QuarkDTO(new QuarkPlainIOProcessor()));

				if ($response) $out->CountSuccessAppend(1);
				else $out->CountFailureAppend(1);
			}

			unset($request, $response);
		}

		return $out;
	}

	/**
	 * @return mixed
	 */
	public function PushNotificationProviderReset () {
		$this->_devices = array();
	}

	/**
	 * @return string
	 */
	public function Token () {
		$request = QuarkDTO::ForRequest(QuarkDTO::METHOD_POST, new QuarkFormIOProcessor());
		$request->Data(array(
			'client_id' => $this->_appID,
			'client_secret' => $this->_appSecret,
			'grant_type' => 'client_credentials',
			'scope' => 'notify.windows.com'
		));

		$response = QuarkHTTPClient::To(self::URL_TOKEN, $request, new QuarkDTO(new QuarkJSONIOProcessor()));

		return isset($response->access_token) ? $response->access_token : null;
	}
}