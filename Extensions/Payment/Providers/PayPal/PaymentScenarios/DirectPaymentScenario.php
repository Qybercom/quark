<?php
namespace Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios;

use Quark\QuarkDTO;

use Quark\Extensions\Payment\IQuarkPaymentConfig;
use Quark\Extensions\Payment\IQuarkPaymentScenario;

/**
 * Class DirectPaymentScenario
 *
 * @package Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios
 */
class DirectPaymentScenario implements IQuarkPaymentScenario {
	
	/**
	 * @param IQuarkPaymentConfig $config
	 *
	 * @return bool
	 */
	public function Pay (IQuarkPaymentConfig $config) {
		// TODO: Implement Pay() method.
	}

	/**
	 * @return QuarkDTO
	 */
	public function Response () {
		// TODO: Implement Response() method.
	}
}