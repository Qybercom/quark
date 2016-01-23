<?php
namespace Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios;

use Quark\QuarkDTO;

use Quark\Extensions\Payment\IQuarkPaymentInstrument;
use Quark\Extensions\Payment\IQuarkPaymentProvider;
use Quark\Extensions\Payment\IQuarkPaymentScenario;

use Quark\Extensions\Payment\Providers\PayPal\PayPal;

/**
 * Class BillingPlanUpdateScenario
 *
 * @package Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios
 */
class BillingPlanUpdateScenario implements IQuarkPaymentScenario {
	/**
	 * @var QuarkDTO $_response
	 */
	private $_response;

	/**
	 * @param IQuarkPaymentProvider|PayPal $provider
	 * @param IQuarkPaymentInstrument $instrument = null
	 *
	 * @return bool
	 */
	public function Proceed (IQuarkPaymentProvider $provider, IQuarkPaymentInstrument $instrument = null) {
		// TODO: Implement Proceed() method.
	}

	/**
	 * @return QuarkDTO
	 */
	public function Response () {
		return $this->_response;
	}
}