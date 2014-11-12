<?php
namespace Quark\Extensions\PushNotification\Providers;

use Quark\Extensions\PushNotification\Device;
use Quark\QuarkClient;
use Quark\QuarkClientDTO;
use Quark\QuarkCredentials;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkHTMLIOProcessor;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkPlainIOProcessor;
use Quark\QuarkXMLIOProcessor;

use Quark\Extensions\PushNotification\Config;
use Quark\Extensions\PushNotification\IPushNotificationProvider;

/**
 * Class Microsoft
 *
 * @package Quark\Extensions\PushNotification\Providers
 */
class Microsoft implements IPushNotificationProvider {
	const TYPE = 'windows';

	private $_config = null;
	private $_access = null;

	/**
	 * @var Device
	 */
	private $_device = '';

	private $_options = array(
		'type' => 'tile',
		'bindings' => array()
	);

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
	 * @return string
	 */
	public function URL () {
		return $this->_device->id;
	}

	public static function Binding ($template, $children = []) {
		$inner = '';

		foreach ($children as $i => $child) $inner .= $child;

		return '
			<binding template="' . $template . '">
				' . $inner . '
			</binding>
		';
	}

	public static function ElementImage ($id, $src, $alt = '') {
		return '<image id="' . $id . '" src="' . $src . ' alt="' . $alt . '" />';
	}

	public static function ElementText ($id, $content, $language = '') {
		return '<text id="' . $id . '"' . (func_num_args() == 3 ? ' language="' . $language . '"' : '') . '>'
				. $content
				. '</text>';
	}

	private function _toast () {
		$title = 'hello';
		$img = 'http://static.home.evolutex.ru/push.png';

		/*return '<?xml version="1.0" encoding="utf-8"?>'.
		'<tile>'.
		'<visual lang="en-US">'.
		'<binding template="TileWideImageAndText01">'.
		'<image id="1" src="'.$img.'"/>'.
		'<text id="1">' . $title . '</text>'.
		'</binding>'.
		'</visual>'.
		'</tile>';*/

		return '<?xml version="1.0" encoding="utf-8"?>'
		.'<toast>'
        .'<visual lang="en-US">'
        .'<binding template="ToastText01">'
        .'<text id="1">New test arrived!</text>'
        .'</binding>'
        .'</visual>'
        .'</toast>';

		//return '<tile><visual><binding template="TileSquareText01"><text id="1">tile one text</text></binding><binding template="TileSquareText02"><text id="2">tile two text</text></binding></visual></tile>';
	}

	/**
	 * @param $payload
	 * @return QuarkClientDTO
	 */
	public function Request ($payload) {
		$request = new QuarkClientDTO();
		//$request->Processor(new QuarkXMLIOProcessor());

		$access = $this->_token();
		$data = $this->_toast();

		$request->Header('Content-Type', 'text/xml');
		$request->Header('Content-Length', strlen($data));
		$request->Header('X-WNS-Type', 'wns/toast');
		$request->Header('Authorization', 'Bearer ' . preg_replace('/\s+/', '', $access->access_token . '='));
		//$request->Header('Authorization', trim($access->token_type . ' ' . $access->access_token));
		/*$request->Header('X-WNS-RequestForStatus', 'true');
		$request->Header('X-WNS-Type', 'wns/' . $this->_options['type']);

		$request->Data(array(
			$this->_options['type'] => array(
				'visual' => $this->_options['bindings']
			)
		));*/
		$request->Data($data);

		return $request;
	}

	/**
	 * @return \Quark\QuarkClientDTO
	 */
	public function Response () {
		$response = new QuarkClientDTO();
		$response->Processor(new QuarkPlainIOProcessor());

		return $response;
	}

	/**
	 * @return mixed
	 */
	private function _token () {
		if ($this->_access != null) return $this->_access;

		$request = new QuarkClientDTO();
		$request->Processor(new QuarkFormIOProcessor());
		$request->Data($this->_config + array(
			'grant_type' => 'client_credentials',
			'scope' => 'notify.windows.com'//'s.notify.live.net'
		));

		$response = new QuarkClientDTO();
		$response->Processor(new QuarkJSONIOProcessor());

		$client = new QuarkClient(
			QuarkCredentials::FromURI('https://login.live.com/accesstoken.srf'),
			$request,
			$response
		);

		print_r($client);

		return $this->_access = $client->Post()->Data();
	}

	/**
	 * @param Device $device
	 */
	public function Device (Device $device) {
		$this->_device = $device;
	}
}