<?php
namespace Quark\Extensions\PubNub;

use Quark\IQuarkExtension;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\Quark;
use Quark\QuarkInlineJSViewResource;
use Quark\QuarkJSViewResourceType;

/**
 * Class PubNub
 *
 * @package Quark\Extensions\PubNub
 */
class PubNub implements IQuarkExtension, IQuarkSpecifiedViewResource, IQuarkViewResourceWithDependencies {
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
	 * @return IQuarkViewResourceType
	 */
	public function Type () {
		return new QuarkJSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return 'https://cdn.pubnub.com/sdk/javascript/pubnub.' . $this->_version . '.min.js';
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