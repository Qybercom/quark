<?php
namespace Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios;

use Quark\QuarkDTO;

use Quark\Extensions\Payment\IQuarkPaymentInstrument;
use Quark\Extensions\Payment\IQuarkPaymentProvider;
use Quark\Extensions\Payment\IQuarkPaymentScenario;

use Quark\Extensions\Payment\Providers\PayPal\PayPal;

/**
 * Class BillingAgreementCancelScenario
 *
 * @package Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios
 */
class BillingAgreementCancelScenario implements IQuarkPaymentScenario {
	/**
	 * @var QuarkDTO $_response
	 */
	private $_response;

	/**
	 * @var string $_agreement = ''
	 */
	private $_agreement = '';

	/**
	 * @param string $agreement = ''
	 */
	public function __construct ($agreement = '') {
		$this->Agreement($agreement);
	}

	/**
	 * @param string $agreement = ''
	 *
	 * @return string
	 */
	public function Agreement ($agreement = '') {
		if (func_num_args() != 0)
			$this->_agreement = $agreement;

		return $this->_agreement;
	}

	/**
	 * @param IQuarkPaymentProvider|PayPal $provider
	 * @param IQuarkPaymentInstrument $instrument = null
	 *
	 * @return bool
	 */
	public function Proceed (IQuarkPaymentProvider $provider, IQuarkPaymentInstrument $instrument = null) {
		$this->_response = $provider->API(
			QuarkDTO::METHOD_POST,
			'/v1/payments/billing-agreements/' . $this->_agreement . '/cancel'
		);

		return $this->_response->Status() == QuarkDTO::STATUS_200_OK;
	}

	/**
	 * @return QuarkDTO
	 */
	public function Response () {
		return $this->_response;
	}
}