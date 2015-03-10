<?php
namespace Quark\Extensions\Payment;

/**
 * Interface IPaymentScenario
 *
 * @package Quark\Extensions\Payment
 */
interface IPaymentScenario {
	/**
	 * @return string
	 */
	function URL();

	/**
	 * @return array
	 */
	function Fields();
}