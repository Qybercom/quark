<?php
namespace Quark\Extensions\Payment\Providers\PayPal;

use Quark\Extensions\Payment\IQuarkPaymentScenario;
use Quark\IQuarkLinkedModel;
use Quark\IQuarkModel;

use Quark\QuarkDate;
use Quark\QuarkDTO;
use Quark\QuarkModel;

use Quark\Extensions\Payment\Payment;
use Quark\Extensions\Payment\Providers\PayPal\PaymentInstruments\PayPalAccountInstrument;
use Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios\BillingAgreementCreateScenario;
use Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios\BillingAgreementExecuteScenario;
use Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios\BillingAgreementUpdateScenario;
use Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios\BillingAgreementGetScenario;
use Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios\BillingAgreementReactivateScenario;
use Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios\BillingAgreementSuspendScenario;
use Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios\BillingAgreementBillBalanceScenario;
use Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios\BillingAgreementCancelScenario;
use Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios\BillingAgreementSetBalanceScenario;
use Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios\BillingAgreementTransactionsScenario;

/**
 * Class PayPalBillingAgreement
 *
 * @property string $id = ''
 *
 * @package Quark\Extensions\Payment\Providers\PayPal
 */
class PayPalBillingAgreement implements IQuarkModel, IQuarkLinkedModel {
	/**
	 * @var Payment $_payment
	 */
	private $_payment;

	/**
	 * @param string $config = ''
	 * @param string $id = ''
	 *
	 * @return QuarkModel|PayPalBillingAgreement
	 */
	public static function Config ($config = '', $id = '') {
		$agreement = new self();
		$agreement->_payment = new Payment($config);
		$agreement->id = $id;

		return $agreement->_payment->Config()->PaymentProvider() instanceof PayPal ? new QuarkModel($agreement) : null;
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
	 * @param string $plan = ''
	 * @param string $name = ''
	 * @param string $description = ''
	 * @param QuarkDate $start = null
	 *
	 * @return BillingAgreementCreateScenario|IQuarkPaymentScenario
	 */
	public function AgreementCreate ($plan = '', $name = '', $description = '', QuarkDate $start = null) {
		return $this->_payment->Scenario(new BillingAgreementCreateScenario($plan, $name, $description, $start));
	}

	/**
	 * @param string $token = ''
	 *
	 * @return BillingAgreementExecuteScenario|IQuarkPaymentScenario
	 */
	public function AgreementExecute ($token) {
		return $this->_payment->Scenario(new BillingAgreementExecuteScenario($token));
	}

	/**
	 * @param QuarkDTO $request
	 *
	 * @return BillingAgreementExecuteScenario|IQuarkPaymentScenario
	 */
	public function AgreementExecuteFromRedirect (QuarkDTO $request) {
		return $this->_payment->Scenario(BillingAgreementExecuteScenario::FromRedirect($request));
	}

	/**
	 * @return BillingAgreementUpdateScenario|IQuarkPaymentScenario
	 */
	public function AgreementUpdate () {
		return $this->_payment->Scenario(new BillingAgreementUpdateScenario());
	}

	/**
	 * @param string $id = ''
	 *
	 * @return BillingAgreementGetScenario|IQuarkPaymentScenario
	 */
	public function AgreementGet ($id = '') {
		if (func_num_args() != 0)
			$this->id = $id;

		return $this->_payment->Scenario(new BillingAgreementGetScenario($this->id));
	}

	/**
	 * @param string $id = ''
	 *
	 * @return BillingAgreementSuspendScenario|IQuarkPaymentScenario
	 */
	public function AgreementSuspend ($id = '') {
		if (func_num_args() != 0)
			$this->id = $id;

		return $this->_payment->Scenario(new BillingAgreementSuspendScenario($this->id));
	}

	/**
	 * @param string $id = ''
	 *
	 * @return BillingAgreementReactivateScenario|IQuarkPaymentScenario
	 */
	public function AgreementReactivate ($id = '') {
		if (func_num_args() != 0)
			$this->id = $id;

		return $this->_payment->Scenario(new BillingAgreementReactivateScenario($this->id));
	}

	/**
	 * @param string $id = ''
	 *
	 * @return BillingAgreementCancelScenario|IQuarkPaymentScenario
	 */
	public function AgreementCancel ($id = '') {
		if (func_num_args() != 0)
			$this->id = $id;

		return $this->_payment->Scenario(new BillingAgreementCancelScenario($this->id));
	}

	/**
	 * @param string $id = ''
	 *
	 * @return BillingAgreementTransactionsScenario|IQuarkPaymentScenario
	 */
	public function AgreementTransactions ($id = '') {
		if (func_num_args() != 0)
			$this->id = $id;

		return $this->_payment->Scenario(new BillingAgreementTransactionsScenario($this->id));
	}

	/**
	 * @param string $id = ''
	 * @param $currency = Payment::CURRENCY_USD
	 * @param int|float $value = 0.0
	 *
	 * @return BillingAgreementSetBalanceScenario|IQuarkPaymentScenario
	 */
	public function AgreementSetBalance ($id = '', $currency = Payment::CURRENCY_USD, $value = 0.0) {
		if (func_num_args() != 0)
			$this->id = $id;

		return $this->_payment->Scenario(new BillingAgreementSetBalanceScenario($this->id, $currency, $value));
	}

	/**
	 * @param string $id = ''
	 * @param $currency = Payment::CURRENCY_USD
	 * @param int|float $value = 0.0
	 * @param string $note = ''
	 *
	 * @return BillingAgreementBillBalanceScenario|IQuarkPaymentScenario
	 */
	public function AgreementBillBalance ($id = '', $currency = Payment::CURRENCY_USD, $value = 0.0, $note = '') {
		if (func_num_args() != 0)
			$this->id = $id;

		return $this->_payment->Scenario(new BillingAgreementBillBalanceScenario($this->id, $currency, $value, $note));
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

		return $scenario instanceof BillingAgreementCreateScenario ? $scenario->Approve() : null;
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