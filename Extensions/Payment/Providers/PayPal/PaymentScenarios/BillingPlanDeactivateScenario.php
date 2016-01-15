<?php
namespace Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios;

use Quark\QuarkDTO;

use Quark\Extensions\Payment\IQuarkPaymentInstrument;
use Quark\Extensions\Payment\IQuarkPaymentProvider;
use Quark\Extensions\Payment\IQuarkPaymentScenario;

use Quark\Extensions\Payment\Providers\PayPal\PayPal;
use Quark\Extensions\Payment\Providers\PayPal\PayPalBilling;

/**
 * Class BillingPlanDeactivateScenario
 *
 * @package Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios
 */
class BillingPlanDeactivateScenario implements IQuarkPaymentScenario {
	/**
	 * @var QuarkDTO $_response
	 */
	private $_response;

	/**
	 * @var string $_plan = ''
	 */
	private $_plan = '';

	/**
	 * @param string $plan = ''
	 */
	public function __construct ($plan = '') {
		$this->Plan($plan);
	}

	/**
	 * @param string $plan = ''
	 *
	 * @return string
	 */
	public function Plan ($plan = '') {
		if (func_num_args() != 0)
			$this->_plan = $plan;

		return $this->_plan;
	}

	/**
	 * @param IQuarkPaymentProvider|PayPal $provider
	 * @param IQuarkPaymentInstrument $instrument = null
	 *
	 * @return bool
	 */
	public function Proceed (IQuarkPaymentProvider $provider, IQuarkPaymentInstrument $instrument = null) {
		$this->_response = $provider->API(
			QuarkDTO::METHOD_PATCH,
			'/v1/payments/billing-plans/' . $this->_plan,
			array(
				array(
					'path' => '/',
					'value' => array('state' => PayPalBilling::STATE_CREATED),
					'op' => 'replace'
				)
			)
		);

		return $this->_response->Status() == 200;
	}

	/**
	 * @return QuarkDTO
	 */
	public function Response () {
		return $this->_response;
	}
}