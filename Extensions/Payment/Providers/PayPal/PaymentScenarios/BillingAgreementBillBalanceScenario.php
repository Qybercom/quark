<?php
namespace Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios;

use Quark\QuarkDTO;

use Quark\Extensions\Payment\IQuarkPaymentInstrument;
use Quark\Extensions\Payment\IQuarkPaymentProvider;
use Quark\Extensions\Payment\IQuarkPaymentScenario;

use Quark\Extensions\Payment\Payment;
use Quark\Extensions\Payment\Providers\PayPal\PayPal;

/**
 * Class BillingAgreementBillBalanceScenario
 *
 * @package Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios
 */
class BillingAgreementBillBalanceScenario implements IQuarkPaymentScenario {
	/**
	 * @var QuarkDTO $_response
	 */
	private $_response;

	/**
	 * @var string $_id = ''
	 */
	private $_id = '';

	/**
	 * @var float $_value = 0.0
	 */
	private $_value = 0.0;

	/**
	 * @var string $_currency = Payment::CURRENCY_USD
	 */
	private $_currency = Payment::CURRENCY_USD;

	/**
	 * @var string $_note = ''
	 */
	private $_note = '';

	/**
	 * @param string $id = ''
	 * @param $currency = Payment::CURRENCY_USD
	 * @param int|float $value = 0.0
	 * @param string $note = ''
	 */
	public function __construct ($id = '', $currency = Payment::CURRENCY_USD, $value = 0.0, $note = '') {
		$this->Id($id);
		$this->Money($currency, $value);
		$this->Note($note);
	}

	/**
	 * @param string $id = ''
	 *
	 * @return string
	 */
	public function Id ($id = '') {
		if (func_num_args() != 0)
			$this->_id = $id;

		return $this->_id;
	}

	/**
	 * @param string $currency = Payment::CURRENCY_USD
	 * @param float $value = 0.0
	 *
	 * @return PaymentCreateScenario
	 */
	public function Money ($currency = Payment::CURRENCY_USD, $value = 0.0) {
		$this->_currency = $currency;
		$this->_value = $value;

		return $this;
	}

	/**
	 * @param string $note = ''
	 *
	 * @return string
	 */
	public function Note ($note = '') {
		if (func_num_args() != 0)
			$this->_note = $note;

		return $this->_note;
	}

	/**
	 * @param IQuarkPaymentProvider|PayPal $provider
	 * @param IQuarkPaymentInstrument $instrument = null
	 *
	 * @return bool
	 */
	public function Proceed (IQuarkPaymentProvider $provider, IQuarkPaymentInstrument $instrument = null) {
		$request = array(
			'note' => $this->_note,
			'amount' => array(
				'currency' => $this->_currency,
				'value' => $this->_value
			)
		);

		$this->_response = $provider->API(
			QuarkDTO::METHOD_POST,
			'/v1/payments/billing-agreements/' . $this->_id . '/bill-balance',
			$request
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