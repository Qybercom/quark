<?php
namespace Quark\Extensions\Payment;

/**
 * Interface IQuarkPaymentScenario
 *
 * @package Quark\Extensions\Payment
 */
interface IQuarkPaymentScenario {
	/**
	 * @param IQuarkPaymentConfig $config
	 *
	 * @return bool
	 */
	public function Pay(IQuarkPaymentConfig $config);
}