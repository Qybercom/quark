<?php
namespace Quark\Extensions\PushNotification\Providers\GoogleFCM;

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
 * Class GoogleFCM
 *
 * @package Quark\Extensions\PushNotification\Providers\GoogleFCM
 */
class GoogleFCM implements IQuarkPushNotificationProvider {
	const TYPE = 'fcm';
	const URL_API = 'https://fcm.googleapis.com/fcm';
	const BULK_MAX = 1000;
	const ERROR_NOT_REGISTERED = 'NotRegistered';

	/**
	 * @var PushNotificationDevice[] $_devices = []
	 */
	private $_devices = array();

	/**
	 * @var string $_apiKey = ''
	 */
	private $_apiKey = '';

	/**
	 * @param string $apiKey = ''
	 *
	 * @return string
	 */
	public function APIKey ($apiKey = '') {
		if (func_num_args() != 0)
			$this->_apiKey = $apiKey;

		return $this->_apiKey;
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
			'APIKey'
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
		return new GoogleFCMDetails();
	}

	/**
	 * @return IQuarkPushNotificationDevice
	 */
	public function PushNotificationProviderDevice () {
		return new GoogleFCMDevice();
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
			$data = null;
			$request = null;
			$response = null;
			$key = null;
			$result = null;
			$i = 0;

			while ($i < $rounds) {
				$data = $details->PushNotificationDetailsData(array(
					'content_available' => true,
					'registration_ids' => array_slice($this->_devices, $i * self::BULK_MAX, self::BULK_MAX),
					'data' => $payload
				));

				$request = QuarkDTO::ForPOST(new QuarkJSONIOProcessor());
				$request->Header(QuarkDTO::HEADER_AUTHORIZATION, 'key=' . $this->_apiKey);
				$request->Data($data);

				$response = QuarkHTTPClient::To(self::URL_API . '/send', $request, new QuarkDTO(new QuarkJSONIOProcessor()));

				if (!$response || !isset($response->results) || !is_array($response->results)) {
					Quark::Log('[PushNotification:GoogleFCM] Error during sending push notification. Response: ' . print_r($response, true), Quark::LOG_WARN);
					$i++;
					continue;
				}

				if (isset($response->success))
					$out->CountSuccessAppend($response->success);

				if (isset($response->failure))
					$out->CountFailureAppend($response->failure);

				if (isset($response->canonical_ids))
					$out->CountCanonicalAppend($response->canonical_ids);

				foreach ($response->results as $key => &$result) {
					if (isset($result->error) && $result->error == self::ERROR_NOT_REGISTERED) {
						$this->_devices[$key]->deleted = true;
						continue;
					}

					if (isset($result->registration_id)) {
						$this->_devices[$key]->id = $result->registration_id;
						continue;
					}
				}

				$i++;
			}

			unset($data, $request, $response, $key, $result, $i);
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