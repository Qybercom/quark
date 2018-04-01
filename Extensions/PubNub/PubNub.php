<?php
namespace Quark\Extensions\PubNub;

use Quark\IQuarkExtension;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\Quark;
use Quark\QuarkDate;
use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkInlineJSViewResource;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkJSViewResourceType;
use Quark\QuarkURI;

/**
 * Class PubNub
 *
 * @package Quark\Extensions\PubNub
 */
class PubNub implements IQuarkExtension, IQuarkSpecifiedViewResource, IQuarkViewResourceWithDependencies {
	const URL_API = 'https://ps.pndsn.com/';
	const URL_SDK = 'https://cdn.pubnub.com/';

	const STATUS_SENT = 'Sent';

	const VERSION_JS = '4.15.1';

	/**
	 * @var PubNubConfig $_config
	 */
	private $_config;

	/**
	 * @var string $_client = ''
	 */
	private $_client = '';

	/**
	 * @var string $_version = self::VERSION_JS
	 */
	private $_version = self::VERSION_JS;

	/**
	 * @param string $config
	 * @param string $client = ''
	 * @param string $version = self::VERSION_JS
	 */
	public function __construct ($config, $client = '', $version = self::VERSION_JS) {
		$this->_config = Quark::Config()->Extension($config);
		$this->_client = func_num_args() > 1 ? $client : $config;
		$this->_version = $version;
	}

	/**
	 * @return PubNubConfig
	 */
	public function &Config () {
		return $this->_config;
	}

	/**
	 * @param string $url = ''
	 * @param array|object $data = []
	 * @param string $method = QuarkDTO::METHOD_GET
	 *
	 * @return bool|QuarkDTO
	 */
	public function API ($url = '', $data = [], $method = QuarkDTO::METHOD_GET) {
		$request = new QuarkDTO(new QuarkJSONIOProcessor());
		$request->Method($method);
		$request->Data($data);

		return QuarkHTTPClient::To(self::URL_API . $url, $request, new QuarkDTO(new QuarkJSONIOProcessor()));
	}

	/**
	 * @param string $channel = ''
	 * @param string $user = ''
	 * @param array|object $payload = []
	 * @param bool $store = false
	 *
	 * @return QuarkDate
	 */
	public function Publish ($channel = '', $user = '', $payload = [], $store = false) {
		$query = array(
			'uuid' => $user
		);

		if (func_num_args() == 4)
			$query['store'] = $store;

		$response = $this->API(
			QuarkURI::Build('/publish/' . $this->_config->appKeyPub . '/' . $this->_config->appKeySub . '/0/' . $channel . '/0', $query),
			$payload,
			QuarkDTO::METHOD_POST
		);

		$data = $response->Data();

		return is_array($data) && $data[1] == self::STATUS_SENT ? QuarkDate::FromTimestamp($data[2] / 1000000) : null;
	}

	/**
	 * @return IQuarkViewResourceType
	 */
	public function Type () {
		return new QuarkJSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return self::URL_SDK . 'sdk/javascript/pubnub.' . $this->_version . '.min.js';
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			$this->_client
				? new QuarkInlineJSViewResource('var ' . $this->_client . '=' . json_encode(array(
					'pub' => $this->_config->appKeyPub,
					'sub' => $this->_config->appKeySub
				)))
				: null
		);
	}
}