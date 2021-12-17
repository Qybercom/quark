<?php
namespace Quark\Extensions\PushNotification\Providers\AppleAPNS;

use Quark\Quark;
use Quark\QuarkCertificate;
use Quark\QuarkClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkModel;
use Quark\QuarkTCPNetworkTransport;

use Quark\Extensions\PushNotification\IQuarkPushNotificationProvider;
use Quark\Extensions\PushNotification\IQuarkPushNotificationDetails;
use Quark\Extensions\PushNotification\IQuarkPushNotificationDevice;

use Quark\Extensions\PushNotification\PushNotificationDevice;
use Quark\Extensions\PushNotification\PushNotificationResult;

/**
 * Class AppleAPNS
 *
 * @package Quark\Extensions\PushNotification\Providers\AppleAPNS
 */
class AppleAPNS implements IQuarkPushNotificationProvider {
	const TYPE = 'ios';

	const URL_API_PRODUCTION = 'ssl://gateway.push.apple.com:2195';
	const URL_API_SANDBOX = 'ssl://gateway.sandbox.push.apple.com:2195';

	/**
	 * @var PushNotificationDevice[] $_devices = []
	 */
	private $_devices = array();

	/**
	 * @var QuarkCertificate $_certificate
	 */
	private $_certificate;

	/**
	 * @var bool $_sandbox = false
	 */
	private $_sandbox = false;

	/**
	 * @var QuarkJSONIOProcessor $_processor
	 */
	private $_processor;

	/**
	 * AppleAPNS constructor
	 */
	public function __construct () {
		$this->_processor = new QuarkJSONIOProcessor();
	}

	/**
	 * @param QuarkCertificate $certificate = null
	 *
	 * @return QuarkCertificate
	 */
	public function &Certificate (QuarkCertificate $certificate = null) {
		if (func_num_args() != 0)
			$this->_certificate = $certificate;

		return $this->_certificate;
	}

	/**
	 * @param string $location = ''
	 *
	 * @return string
	 */
	public function CertificateLocation ($location = '') {
		if (func_num_args() != 0)
			$this->Certificate(QuarkCertificate::FromLocation($location));

		return $this->_certificate->Location();
	}

	/**
	 * @param string $passphrase = null
	 *
	 * @return string
	 */
	public function CertificatePassphrase ($passphrase = null) {
		if (func_num_args() != 0)
			$this->_certificate->Passphrase($passphrase);

		return $this->_certificate->Passphrase();
	}

	/**
	 * @param bool $sandbox = false
	 *
	 * @return bool
	 */
	public function Sandbox ($sandbox = false) {
		if (func_num_args() != 0)
			$this->_sandbox = $sandbox;

		return $this->_sandbox;
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
			'CertificateLocation',
			'CertificatePassphrase',
			'Sandbox'
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
		return new AppleAPNSDetails();
	}

	/**
	 * @return IQuarkPushNotificationDevice
	 */
	public function PushNotificationProviderDevice () {
		return new AppleAPNSDevice();
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

		if ($this->_certificate == null) {
			Quark::Log('[PushNotification:AppleAPNS] Certificate was not specified or given path for ios.Certificate.Location in "ini" was not resolved', Quark::LOG_WARN);
			return $out;
		}

		$payloadOut = $this->_processor->Encode($details->PushNotificationDetailsData($payload));

		usort($this->_devices, function ($a, $b) {
			$date = $b->date instanceof QuarkModel ? $b->date->Model() : $b->date;

			if (!isset($a->date)) return 1;

			return $a->date->Earlier($date)
				? 1
				: ($a->date->Later($date)
					? -1
					: 0
				);
		});

		$client = new QuarkClient(
			$this->_sandbox ? self::URL_API_SANDBOX : self::URL_API_PRODUCTION,
			new QuarkTCPNetworkTransport(),
			$this->_certificate,
			60,
			false
		);

		$client->On(QuarkClient::EVENT_CONNECT, function (QuarkClient $client) use (&$payloadOut, &$out) {
			foreach ($this->_devices as $i => &$device)
				$out->CountSuccessAppend($client->Send(self::Message($device->id, $payloadOut)) ? 1 : 0);

			unset($i, $device);
		});

		$client->On(QuarkClient::EVENT_ERROR_CONNECT, function ($error) {
			Quark::Log($error, Quark::LOG_WARN);
		});

		$client->Connect();

		return $out;
	}

	/**
	 * @return mixed
	 */
	public function PushNotificationProviderReset () {
		$this->_devices = array();
	}

	/**
	 * @param string $deviceID = ''
	 * @param string $payload = ''
	 *
	 * @return string
	 */
	public static function Message ($deviceID = '', $payload = '') {
		return chr(0) . pack('n', 32) . pack('H*', str_replace('<', '', str_replace('>', '', str_replace(' ', '', $deviceID)))) . pack('n', strlen($payload)) . $payload;
	}
}