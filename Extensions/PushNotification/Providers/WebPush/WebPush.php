<?php
namespace Quark\Extensions\PushNotification\Providers\WebPush;

use Quark\IQuarkSpecifiedViewResource;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkURI;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkView;
use Quark\QuarkEncryptionKey;

use Quark\Extensions\Quark\EncryptionAlgorithms\EncryptionAlgorithmEC;

use Quark\Extensions\PushNotification\IQuarkPushNotificationDevice;
use Quark\Extensions\PushNotification\IQuarkPushNotificationProvider;
use Quark\Extensions\PushNotification\IQuarkPushNotificationDetails;

use Quark\Extensions\PushNotification\PushNotificationDevice;
use Quark\Extensions\PushNotification\PushNotificationResult;

/**
 * Class WebPush
 *
 * https://datatracker.ietf.org/doc/html/rfc8291
 * https://github.com/web-push-libs/web-push-php
 *
 * https://onesignal.com/blog/apples-plans-to-support-ios-web-push-in-2021/
 *
 * @package Quark\Extensions\PushNotification\Providers\WebPush
 */
class WebPush implements IQuarkPushNotificationProvider {
	const TYPE = 'web';

	const PREFIX_CONTENT_ENCODING = 'Content-Encoding: ';

	const PAYLOAD_ENCRYPTION_CIPHER_AES128GCM = 'aes-128-gcm';

	const LENGTH_SALT = 16;
	const LENGTH_COMPONENT_IKM = 32;
	const LENGTH_COMPONENT_CONTENT_ENCRYPTION_KEY = 16;
	const LENGTH_COMPONENT_NONCE = 12;

	const HDKF_INFO_TYPE_NONCE = 'nonce';

	const HEADER_ENCRYPTION = 'Encryption';
	const HEADER_CRYPTO_KEY = 'Crypto-Key';
	const HEADER_TOPIC = 'Topic';
	const HEADER_URGENCY = 'Urgency';
	const HEADER_TTL = 'TTL';

	/**
	 * @var PushNotificationDevice[] $_devices = []
	 */
	private $_devices = array();

	/**
	 * @var QuarkEncryptionKey $_key
	 */
	private $_key;

	/**
	 * @var bool $_debug = false
	 */
	private $_debug = false;

	/**
	 * @var string $_config = ''
	 */
	private $_config = '';

	/**
	 * @param QuarkEncryptionKey $key = null
	 *
	 * @return QuarkEncryptionKey
	 */
	public function &Key (QuarkEncryptionKey $key = null) {
		if (func_num_args() != 0)
			$this->_key = $key;

		return $this->_key;
	}

	/**
	 * @param string $location = ''
	 *
	 * @return string
	 */
	public function KeyLocation ($location = '') {
		if (func_num_args() != 0)
			$this->Key(QuarkEncryptionKey::FromFileLocation($location, new EncryptionAlgorithmEC()));

		return $this->_key->File()->Location();
	}

	/**
	 * @param bool $debug = false
	 *
	 * @return bool
	 */
	public function Debug ($debug = false) {
		if (func_num_args() != 0)
			$this->_debug = $debug;

		return $this->_debug;
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
			'KeyLocation',
			'Debug'
		);
	}

	/**
	 * @param string $config
	 *
	 * @return mixed
	 */
	public function PushNotificationProviderInit ($config) {
		$this->_config = $config;
	}

	/**
	 * @return IQuarkPushNotificationDetails
	 */
	public function PushNotificationProviderDetails () {
		return new WebPushDetails();
	}

	/**
	 * @return IQuarkPushNotificationDevice
	 */
	public function PushNotificationProviderDevice () {
		return new WebPushDevice();
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
	 * @param IQuarkPushNotificationDetails|WebPushDetails $details
	 * @param object|array $payload
	 *
	 * @return PushNotificationResult
	 */
	public function PushNotificationProviderSend (IQuarkPushNotificationDetails &$details, $payload) {
		if ($details->KeyVAPID() == null)
			$details->KeyVAPID($this->_key);

		$out = new PushNotificationResult();
		$request = null;

		foreach ($this->_devices as $i => &$item) {
			/**
			 * @var WebPushDevice $device
			 */
			$device = $item->ToDevice(new WebPushDevice());
			$request = $details->PushNotificationDetailsData($payload, $device);

			if ($request == null) {
				Quark::Log('[PushNotification:WebPush] Can not populate request', Quark::LOG_WARN);

				continue;
			}

			$response = QuarkHTTPClient::To($device->Endpoint(), $request, new QuarkDTO(new QuarkJSONIOProcessor()), null, 10, true, $this->_debug);

			if ($response->StatusCode() == QuarkDTO::STATUS_201_CREATED) $out->CountSuccessAppend(1);
			elseif ($response->StatusCode() == QuarkDTO::STATUS_410_GONE) {
				$out->CountFailureAppend(1);
				$this->_devices[$i]->deleted = true;
			}
			else {
				Quark::Log('[PushNotification:WebPush] Can not send push notification request');

				if ($this->_debug) {
					Quark::Trace($request);
					Quark::Trace($response);
				}

				$out->CountFailureAppend(1);
			}
		}

		unset($i, $device, $request);

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
	public function VAPID () {
		return QuarkURI::Base64Encode(EncryptionAlgorithmEC::SECEncode($this->_key));
	}

	/**
	 * @param string $urlDeviceRegistration = ''
	 * @param string $custom = null
	 * @param string $scope = '/'
	 *
	 * @return QuarkDTO
	 */
	public function ServiceWorker ($urlDeviceRegistration = '', $custom = null, $scope = '/') {
		return QuarkDTO::ForServiceWorker($custom, $scope, array(), array(
			new WebPushServiceWorker($this->VAPID(), $urlDeviceRegistration)
		));
	}
}