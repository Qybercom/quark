<?php
namespace Quark\Extensions\Payment;

/**
 * Interface IPaymentScenario
 *
 * @package Quark\Extensions\Payment
 */
interface IPaymentScenario {
	/**
	 * @param $currency
	 * @param $amount
	 *
	 * @return bool
	 */
	function Pay($currency, $amount);
}