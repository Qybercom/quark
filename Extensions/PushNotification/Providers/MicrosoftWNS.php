<?php
namespace Quark\Extensions\PushNotification\Providers;

use Quark\QuarkDTO;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkPlainIOProcessor;

use Quark\Extensions\PushNotification\IQuarkPushNotificationProvider;

use Quark\Extensions\PushNotification\Device;

/**
 * Class MicrosoftWNS
 *
 * @package Quark\Extensions\PushNotification\Providers
 */
class MicrosoftWNS implements IQuarkPushNotificationProvider {
	const TYPE = 'windows';

	const OPTION_CLIENT_ID = 'client_id';
	const OPTION_CLIENT_SECRET = 'client_secret';

	const OPTION_TYPE = 'type';
	const OPTION_VALUE = 'value';
	const OPTION_VISUAL = 'visual';

	const HEADER_STATUS = 'X-WNS-STATUS';
	const HEADER_NOTIFICATION_STATUS = 'X-WNS-NOTIFICATIONSTATUS';
	const HEADER_MESSAGE_ID = 'X-WNS-MSG-ID';
	const HEADER_DEBUG_TRACE = 'X-WNS-DEBUG-TRACE';

	const STATUS_RECEIVED = 'received';
	const STATUS_DROPPED = 'dropped';

	const TYPE_TOAST = 'wns/toast';
	const TYPE_TILE = 'wns/tile';
	const TYPE_BADGE = 'wns/badge';

	/**
	 * @var array $_config = null
	 */
	private $_config = null;

	/**
	 * @var Device[] $_devices = []
	 */
	private $_devices = array();

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

		$this->_config = $config;
	}

	/**
	 * @param string $key
	 * @param $value
	 *
	 * @return mixed
	 */
	public function PNPOption ($key, $value) {
		// TODO: Implement PNPOption() method.
	}

	/**
	 * @param Device $device
	 */
	public function PNPDevice (Device &$device) {
		$this->_devices[] = $device;
	}

	/**
	 * @return Device[]
	 */
	public function &PNPDevices () {
		return $this->_devices;
	}

	/**
	 * @return string
	 */
	private function _token () {
		$request = new QuarkDTO(new QuarkFormIOProcessor());
		$request->Method('POST');
		$request->Data($this->_config + array(
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
	public function PNPSend($payload, $options = []) {
		$type = isset($options[self::OPTION_TYPE]) ? $options[self::OPTION_TYPE] : self::TYPE_TOAST;
		$badge = isset($options[self::OPTION_VALUE]) ? $options[self::OPTION_VALUE] : null;

		$visual = '';
		$templates = isset($options[self::OPTION_VISUAL]) && is_array($options[self::OPTION_VISUAL])
			? $options[self::OPTION_VISUAL]
			: array();

		foreach ($templates as $elem)
			if ($elem instanceof MicrosoftNotificationTemplate)
				$visual .= $elem->Binding();

		$request = new QuarkDTO(new QuarkPlainIOProcessor());
		$request->Method('POST');
		$request->Header(QuarkDTO::HEADER_AUTHORIZATION, 'Bearer ' . $this->_token());
		$request->Header('X-WNS-Type', $type);
		$request->Header(QuarkDTO::HEADER_CONTENT_TYPE, 'text/xml');

		$type = str_replace('wns/', '', $type);

		$data = '<?xml version="1.0" encoding="utf-8"?>
			<' . $type . ($badge === null ? '' : ' value="' . $badge . '"') .'>'
				. ($type == self::TYPE_BADGE ? '' : '<visual>' . $visual . '</visual>')
				. '<data>' . json_encode($payload) . '</data>
			</' . $type . '>';

		$request->Data($data);

		$response = new QuarkDTO(new QuarkPlainIOProcessor());

		foreach ($this->_devices as $device)
			QuarkHTTPClient::To($device->id, $request, $response);
	}

	/**
	 * @return mixed
	 */
	public function PNPReset () {
		$this->_devices = array();
	}
}

/**
 * Class MicrosoftNotificationTemplate
 *
 * @package Quark\Extensions\PushNotification\Providers
 */
class MicrosoftNotificationTemplate {
	const TOAST_TEXT_02 = 'ToastText02';

	/**
	 * @var string $_name
	 */
	private $_name = '';

	/**
	 * @var string $_fallback
	 */
	private $_fallback = '';

	/**
	 * @var string $_elements
	 */
	private $_elements = '';

	/**
	 * @var int $_images
	 */
	private $_images = 1;

	/**
	 * @var int $_texts
	 */
	private $_texts = 1;

	/**
	 * @param string $name
	 * @param string $fallback
	 */
	public function __construct ($name, $fallback = '') {
		$this->_name = $name;
		$this->_fallback = $fallback;

		$this->_images = 1;
		$this->_texts = 1;
	}

	/**
	 * @param $elem
	 * @param $id
	 *
	 * @return mixed
	 */
	public function _id ($elem, $id) {
		$elem = '_' . $elem . 's';

		return $id === null || !is_scalar($id) ? $this->$elem++ : $id;
	}

	/**
	 * @param string $contents
	 * @param string $id
	 *
	 * @return MicrosoftNotificationTemplate
	 */
	public function Text ($contents, $id = null) {
		$this->_elements .= '<text id="' . $this->_id('text', $id) . '">' . $contents . '</text>';

		return $this;
	}

	/**
	 * @param string $src
	 * @param string $alt
	 * @param string $id
	 *
	 * @return MicrosoftNotificationTemplate
	 */
	public function Image ($src, $alt = '', $id = null) {
		$this->_elements .= '<image id="' . $this->_id('image', $id) . '" src="' . $src . '" alt="' . $alt . '" />';

		return $this;
	}

	/**
	 * @return string
	 */
	public function Binding () {
		return '<binding template="' . $this->_name . (strlen($this->_fallback) == 0 ? '' : ' fallback="' . $this->_fallback . '"') . '">'
				. $this->_elements
			. '</binding>';
	}
}