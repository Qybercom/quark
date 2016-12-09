<?php
namespace Quark\Extensions\PushNotification\Providers\MicrosoftWNS;

use Quark\QuarkDTO;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkPlainIOProcessor;
use Quark\QuarkXMLIOProcessor;
use Quark\QuarkXMLNode;

use Quark\Extensions\PushNotification\IQuarkPushNotificationProvider;
use Quark\Extensions\PushNotification\IQuarkPushNotificationDetails;

use Quark\Extensions\PushNotification\Device;

/**
 * Class MicrosoftWNS
 *
 * @package Quark\Extensions\PushNotification\Providers\MicrosoftWNS
 */
class MicrosoftWNS implements IQuarkPushNotificationProvider {
	const TYPE = 'windows';

	const OPTION_CLIENT_ID = 'client_id';
	const OPTION_CLIENT_SECRET = 'client_secret';

	const HEADER_STATUS = 'X-WNS-STATUS';
	const HEADER_NOTIFICATION_STATUS = 'X-WNS-NOTIFICATIONSTATUS';
	const HEADER_MESSAGE_ID = 'X-WNS-MSG-ID';
	const HEADER_DEBUG_TRACE = 'X-WNS-DEBUG-TRACE';
	const HEADER_TYPE = 'X-WNS-Type';

	const STATUS_RECEIVED = 'received';
	const STATUS_DROPPED = 'dropped';

	/**
	 * @var Device[] $_devices = []
	 */
	private $_devices = array();

	/**
	 * @var IQuarkPushNotificationDetails|MicrosoftWNSDetails $_details
	 */
	private $_details;

	/**
	 * @var array $_config = []
	 */
	private $_config = array();

	/**
	 * MicrosoftWNS constructor.
	 */
	public function __construct () {
		$this->_details = new MicrosoftWNSDetails();
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
		if (!is_array($config)) return;

		$this->_config = (array)$config;
	}

	/**
	 * @param string $key
	 * @param $value
	 *
	 * @return void
	 */
	public function PNPOption ($key, $value) {
		switch ($key) {
			case self::OPTION_CLIENT_ID: $this->_config[self::OPTION_CLIENT_ID] = $value; break;
			case self::OPTION_CLIENT_SECRET: $this->_config[self::OPTION_CLIENT_SECRET] = $value; break;
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
	 * @return void
	 */
	public function PNPDetails (IQuarkPushNotificationDetails $details) {
		$this->_details = $details;
	}

	/**
	 * @return string
	 */
	private function _token () {
		$request = new QuarkDTO(new QuarkFormIOProcessor());
		$request->Method('POST');
		$request->Data((array)$this->_config + array(
				'grant_type' => 'client_credentials',
				'scope' => 'notify.windows.com'
			));

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		return QuarkHTTPClient::To('https://login.live.com/accesstoken.srf', $request, $response)->access_token;
	}

	/**
	 * @param object|array $payload
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function PNPSend($payload, $options) {
		$type = str_replace('wns/', '', $this->_details->Type());

		$root = new QuarkXMLNode($type);
		$data = array('data' => json_encode($payload));

		$value = $this->_details->Value();
		if ($value !== null) $root->Attribute('value', $value);

		$visual = $this->_details->PNDetails($payload, $options);
		if ($visual !== null) $data['visual'] = $visual;

		$request = QuarkDTO::ForPOST(new QuarkXMLIOProcessor($root, QuarkXMLIOProcessor::ITEM, false));
		$request->Header(QuarkDTO::HEADER_AUTHORIZATION, 'Bearer ' . $this->_token());
		$request->Header(self::HEADER_TYPE, $type);
		$request->Data($data);

		$response = new QuarkDTO(new QuarkPlainIOProcessor());

		foreach ($this->_devices as $device)
			QuarkHTTPClient::To($device->id, $request, $response);

		return true;
	}

	/**
	 * @return void
	 */
	public function PNPReset () {
		$this->_devices = array();
	}
}