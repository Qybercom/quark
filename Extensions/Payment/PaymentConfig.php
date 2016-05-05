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
	 * @param IQuarkPaymentProvider $provider
	 * @param string $id
	 * @param string $secret
	 */
	public function __construct (IQuarkPaymentProvider $provider, $id, $secret) {
		$this->_provider = $provider;
		$this->appId = $id;
		$this->appSecret = $secret;

		$this->_provider->PaymentProviderApplication($this->appId, $this->appSecret);
	}

	/**
	 * @return array
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
		// TODO: Implement Stacked() method.
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return $this->_provider;
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