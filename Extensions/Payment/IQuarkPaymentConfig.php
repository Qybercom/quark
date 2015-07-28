<?php
namespace Quark\Extensions\Payment;

use Quark\IQuarkExtensionConfig;

/**
 * Interface IQuarkPaymentConfig
 *
 * @package Quark\Extensions\Payment
 */
interface IQuarkPaymentConfig extends IQuarkExtensionConfig {
	/**
	 * @param string $currency
	 * @param float $amount
	 *
	 * @return mixed
	 */
	public function Money($currency, $amount);
}