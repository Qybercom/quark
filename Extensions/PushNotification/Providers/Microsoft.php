<?php
namespace Quark\Extensions\PushNotification\Providers;

use Quark\QuarkClient;
use Quark\QuarkDTO;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkHTTPTransport;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkPlainIOProcessor;

use Quark\Extensions\PushNotification\Device;
use Quark\Extensions\PushNotification\IPushNotificationProvider;

/**
 * Class Microsoft
 *
 * @package Quark\Extensions\PushNotification\Providers
 */
class Microsoft implements IPushNotificationProvider {
	const TYPE = 'windows';

	const OPTION_CLIENT_ID = 'client_id';
	const OPTIONS_CLIENT_SECRET = 'client_secret';

	const HEADER_STATUS = 'X-WNS-STATUS';
	const HEADER_NOTIFICATION_STATUS = 'X-WNS-NOTIFICATIONSTATUS';
	const HEADER_MESSAGE_ID = 'X-WNS-MSG-ID';
	const HEADER_DEBUG_TRACE = 'X-WNS-DEBUG-TRACE';

	const STATUS_RECEIVED = 'received';
	const STATUS_DROPPED = 'dropped';

	const TYPE_TOAST = 'wns/toast';
	const TYPE_TILE = 'wns/tile';
	const TYPE_BADGE = 'wns/badge';

	private $_config = null;

	/**
	 * @var Device[] $_device
	 */
	private $_devices = array();

	/**
	 * @param $config
	 */
	public function Config ($config) {
		if (!is_array($config)) return;

		$this->_config = $config;
	}

	/**
	 * @return string
	 */
	public function Type () {
		return self::TYPE;
	}

	/**
	 * @param Device $device
	 */
	public function Device (Device $device) {
		$this->_devices[] = $device;
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

		$client = new QuarkClient('https://login.live.com/accesstoken.srf', new QuarkHTTPTransport($request, $response));

		return $client->Action()->access_token;
	}

	/**
	 * @param $payload
	 *
	 * @return mixed
	 */
	public function Send ($payload) {
		$request = new QuarkDTO(new QuarkPlainIOProcessor());
		$request->Method('POST');
		$request->Header(QuarkDTO::HEADER_AUTHORIZATION, 'Bearer ' . $this->_token());
		$request->Header('X-WNS-Type', 'wns/toast');
		$request->Header(QuarkDTO::HEADER_CONTENT_TYPE, 'text/xml');

		$data = '<?xml version="1.0" encoding="utf-8"?>
			<toast>
				<visual>
					<binding template="ToastText02">
						<text id="1">' . $payload->header . '</text>
						<text id="2">' . $payload->comment . '</text>
					</binding>
				</visual>
			</toast>';

		$request->Data($data);

		$response = new QuarkDTO(new QuarkPlainIOProcessor());

		foreach ($this->_devices as $device) {
			$channel = new QuarkClient($device->id, new QuarkHTTPTransport($request, $response));
			$channel->Action();
		}
	}
}