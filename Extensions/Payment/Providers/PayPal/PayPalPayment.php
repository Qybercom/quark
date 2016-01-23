<?php
namespace Quark\Extensions\Payment\Providers\PayPal;

use Quark\IQuarkLinkedModel;
use Quark\IQuarkModel;

use Quark\QuarkDTO;
use Quark\QuarkModel;

use Quark\Extensions\Payment\Payment;
use Quark\Extensions\Payment\Providers\PayPal\PaymentInstruments\PayPalAccountInstrument;
use Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios\PaymentCreateScenario;
use Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios\PaymentExecuteScenario;

/**
 * Class PayPalPayment
 *
 * @property string $id = ''
 *
 * @package Quark\Extensions\Payment\Providers\PayPal
 */
class PayPalPayment implements IQuarkModel, IQuarkLinkedModel {
	/**
	 * @var Payment $_payment
	 */
	private $_payment;

	/**
	 * @param string $config = ''
	 * @param string $id = ''
	 *
	 * @return QuarkModel|PayPalBillingPlan
	 */
	public static function Config ($config = '', $id = '') {
		$payment = new self();
		$payment->_payment = new Payment($config);
		$payment->id = $id;

		return $payment->_payment->Config()->PaymentProvider() instanceof PayPal ? new QuarkModel($payment) : null;
	}

	/**
	 * @return bool
	 */
	public function Proceed () {
		return $this->_payment->Proceed();
	}

	/**
	 * @return QuarkDTO
	 */
	public function Response () {
		return $this->_payment->Response();
	}

	/**
	 * @param string $currency = Payment::CURRENCY_USD
	 * @param float $value = 0.0
	 * @param string $return = ''
	 * @param string $cancel = ''
	 * @param string $description = ''
	 *
	 * @return PaymentCreateScenario
	 */
	public function PaymentCreate ($currency = Payment::CURRENCY_USD, $value = 0.0, $return = '', $cancel = '', $description = '') {
		return $this->_payment->Scenario(new PaymentCreateScenario($currency, $value, $return, $cancel, $description));
	}

	/**
	 * @param string $payment = ''
	 * @param string $payer = ''
	 *
	 * @return PaymentExecuteScenario
	 */
	public function PaymentExecute ($payment = '', $payer = '') {
		if (func_num_args() != 0)
			$this->id = $payment;

		return $this->_payment->Scenario(new PaymentExecuteScenario($this->id, $payer));
	}

	/**
	 * @param QuarkDTO $request
	 *
	 * @return PaymentExecuteScenario
	 */
	public function PaymentExecuteFromRedirect (QuarkDTO $request) {
		return $this->_payment->Scenario(PaymentExecuteScenario::FromRedirect($request));
	}

	/**
	 * @return bool
	 */
	public function NeedsApprove () {
		return $this->_payment->Instrument() instanceof PayPalAccountInstrument;
	}

	/**
	 * @return QuarkDTO
	 */
	public function Approve () {
		$scenario = $this->_payment->Scenario();

		return $scenario instanceof PaymentCreateScenario ? $scenario->Approve() : null;
	}

	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'id' => ''
		);
	}

	/**
	 * @return mixed
	 */
	public function Rules () {
		// TODO: Implement Rules() method.
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Link ($raw) {
		return new QuarkModel(new PayPalBillingPlan(), array(
			'id' => $raw
		));
	}

	/**
	 * @return mixed
	 */
	public function Unlink () {
		return $this->id;
	}
}