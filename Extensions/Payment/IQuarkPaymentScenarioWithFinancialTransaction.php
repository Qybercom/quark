<?php
namespace Quark\Extensions\Payment;

/**
 * Interface IQuarkPaymentScenarioWithFinancialTransaction
 *
 * @package Quark\Extensions\Payment
 */
interface IQuarkPaymentScenarioWithFinancialTransaction extends IQuarkPaymentScenario {
	/**
	 * @return PaymentFinancialTransaction
	 */
	public function FinancialTransaction();
}