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
	 * @return string
	 */
	public function Type () {
		return 'windows';
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

	/**
	 * @return QuarkClientDTO
	 */
	public function Request () {
		$request = new QuarkClientDTO();
		$request->Processor(new QuarkXMLIOProcessor());

		$access = $this->_token();

		$request->Header('Authorization', trim($access->token_type . ' ' . $access->access_token));
		$request->Header('X-WNS-RequestForStatus', 'true');
		$request->Header('X-WNS-Type', 'wns/' . $this->_options['type']);

		$request->Data(array(
			$this->_options['type'] => array(
				'visual' => $this->_options['bindings']
			)
		));

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
	 *
	 */
	public function Config () {
		// TODO: Implement Options() method.
	}

	/**
	 * @return mixed
	 */
	private function _token () {
		if ($this->_access != null) return $this->_access;

		$request = new QuarkClientDTO();
		$request->Processor(new QuarkFormIOProcessor());
		$request->Data(array(
			'grant_type' => 'client_credentials',
			'client_id' => '000000004012B814',
			'client_secret' => 'YTUrLvFDDD/EfsnHJEC45+190i8kXAj6',
			'scope' => 'notify.windows.com'
		));

		$response = new QuarkClientDTO();
		$response->Processor(new QuarkJSONIOProcessor());

		$client = new QuarkClient(
			QuarkCredentials::FromURI('https://login.live.com/accesstoken.srf'),
			$request,
			$response
		);

		return $this->_access = $client->Post()->Data();
	}

	/**
	 * @param array $opt
	 */
	public function Options ($opt) {
		$this->_options = $opt;
	}

	/**
	 * @param Device $device
	 */
	public function Device ($device) {
		$this->_device = $device;
	}
}