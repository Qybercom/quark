<?php
namespace Quark\Extensions\Payment;

use Quark\QuarkDTO;

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

	/**
	 * @return QuarkDTO
	 */
	public function Response();
}