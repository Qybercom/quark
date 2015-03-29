<?php
namespace Quark\Extensions\Payment;

/**
 * Interface IQuarkPaymentConfig
 *
 * @package Quark\Extensions\Payment
 */
interface IQuarkPaymentConfig {
	/**
	 * @param string $currency
	 * @param float $amount
	 *
	 * @return mixed
	 */
	public function Money($currency, $amount);
}