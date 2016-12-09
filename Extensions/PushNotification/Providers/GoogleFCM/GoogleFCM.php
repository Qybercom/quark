<?php
namespace Quark\Extensions\PushNotification\Providers\GoogleFCM;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;

use Quark\Extensions\PushNotification\IQuarkPushNotificationProvider;
use Quark\Extensions\PushNotification\IQuarkPushNotificationDetails;

use Quark\Extensions\PushNotification\Device;

/**
 * Class GoogleFCM
 *
 * @package Quark\Extensions\PushNotification\Providers\GoogleFCM
 */
class GoogleFCM implements IQuarkPushNotificationProvider {
	const TYPE = 'fcm';
	const BULK_MAX = 1000;
	const ERROR_NOT_REGISTERED = 'NotRegistered';

	/**
	 * @var Device[] $_devices = []
	 */
	private $_devices = array();

	/**
	 * @var IQuarkPushNotificationDetails|GoogleFCMDetails $_details
	 */
	private $_details;

	/**
	 * @var string $_key = ''
	 */
	private $_key = '';

	/**
	 * GoogleFCM constructor.
	 */
	public function __construct () {
		$this->_details = new GoogleFCMDetails();
	}

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
		if (is_string($config)) {
			$this->_key = $config;
			return;
		}
	}

	/**
	 * @param string $key
	 * @param $value
	 *
	 * @return void
	 */
	public function PNPOption ($key, $value) {
		if (is_string($value)) {
			$this->_key = $value;
			return;
		}
	}

	/**
	 * @param Device &$device
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
	 * @return void
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
		$changed = sizeof($this->_details->Changes()) != 0;

		while ($i < $queues) {
			$data = array(
				'registration_ids' => array_slice($devices, $i * self::BULK_MAX, self::BULK_MAX),
				'data' => $payload,
			);

			if ($changed)
				$data['notification'] = $this->_details->PNDetails($payload, $options);

			$request = QuarkDTO::ForPOST(new QuarkJSONIOProcessor());
			$request->Header(QuarkDTO::HEADER_AUTHORIZATION, 'key=' . $this->_key);
			$request->Data($data);

			$response = new QuarkDTO(new QuarkJSONIOProcessor());

			$out = QuarkHTTPClient::To('https://fcm.googleapis.com/fcm/send', $request, $response);

			if (!$out || !isset($out->results) || !is_array($out->results)) {
				Quark::Log('[GoogleFCM] Error during sending push notification. Google FCM (Firebase) response: ' . print_r($out, true), Quark::LOG_WARN);
				return false;
			}

			Quark::Log('[GoogleFCM] Push notification sent. Results: [success:' . $out->success . ', failure:' . $out->failure . ', canonical:' . $out->canonical_ids . ']');

			foreach ($out->results as $key => $result) {
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

		return true;
	}

	/**
	 * @return void
	 */
	public function PNPReset () {
		$this->_devices = array();
	}
}