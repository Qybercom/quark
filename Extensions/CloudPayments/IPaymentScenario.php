<?php
namespace Quark\Extensions\CloudPayments;

/**
 * Interface IPaymentScenario
 *
 * @package Quark\Extensions\CloudPayments
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