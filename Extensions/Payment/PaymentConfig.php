<?php
namespace Quark\Extensions\Payment;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;

use Quark\Quark;

/**
 * Class PaymentConfig
 *
 * @package Quark\Extensions\Payment
 */
class PaymentConfig implements IQuarkExtensionConfig {
	/**
	 * @var IQuarkPaymentProvider $_provider
	 */
	private $_provider;

	/**
	 * @var string $appId = ''
	 */
	public $appId = '';

	/**
	 * @var string $appSecret = ''
	 */
	public $appSecret = '';

	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @param IQuarkPaymentProvider $provider
	 * @param string $id = ''
	 * @param string $secret = ''
	 */
	public function __construct (IQuarkPaymentProvider $provider, $id = '', $secret = '') {
		$this->_provider = $provider;
		$this->appId = $id;
		$this->appSecret = $secret;

		$this->_provider->PaymentProviderApplication($this->appId, $this->appSecret, null);
	}

	/**
	 * @return object
	 */
	public function Credentials () {
		return (object)array(
			'appId' => $this->appId,
			'appSecret' => $this->appSecret
		);
	}

	/**
	 * @return IQuarkPaymentProvider
	 */
	public function &PaymentProvider () {
		return $this->_provider;
	}

	/**
	 * @param string $name
	 */
	public function Stacked ($name) {
		$this->_name = $name;
	}

	/**
	 * @return string
	 */
	public function ExtensionName () {
		return $this->_name;
	}

	/**
	 * @param object $ini
	 *
	 * @return mixed
	 */
	public function ExtensionOptions ($ini) {
		if (isset($ini->AppID))
			$this->appId = $ini->AppID;

		if (isset($ini->AppSecret))
			$this->appSecret = $ini->AppSecret;

		$this->_provider->PaymentProviderApplication($this->appId, $this->appSecret, $ini);
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new Payment($this->_name);
	}

	/**
	 * @param string $config
	 *
	 * @return PaymentConfig
	 */
	public static function Instance ($config) {
		$provider = Quark::Config()->Extension($config);

		return $provider instanceof PaymentConfig ? $provider : null;
	}
}