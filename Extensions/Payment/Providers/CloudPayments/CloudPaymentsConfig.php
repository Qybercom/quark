<?php
namespace Quark\Extensions\Payment\Providers\CloudPayments;

use Quark\IQuarkExtensionConfig;
use Quark\Extensions\Payment\IQuarkPaymentConfig;

/**
 * Class CloudPaymentsConfig
 *
 * @package Quark\Extensions\Payment\Providers
 */
class CloudPaymentsConfig implements IQuarkExtensionConfig, IQuarkPaymentConfig {
	public $user;
	public $pass;

	public $amount;
	public $currency;

	/**
	 * @param string $user
	 * @param string $password
	 */
	public function __construct ($user, $password) {
		$this->user = $user;
		$this->pass = $password;
	}

	/**
	 * @param string $currency
	 * @param float $amount
	 *
	 * @return mixed
	 */
	public function Money ($currency, $amount) {
		$this->currency = $currency;
		$this->amount = $amount;
	}
}