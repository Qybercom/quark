<?php
namespace Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios;

use Quark\QuarkDate;
use Quark\QuarkDTO;
use Quark\QuarkURI;

use Quark\Extensions\Payment\IQuarkPaymentInstrument;
use Quark\Extensions\Payment\IQuarkPaymentProvider;
use Quark\Extensions\Payment\IQuarkPaymentScenario;

use Quark\Extensions\Payment\Providers\PayPal\PayPal;

/**
 * Class BillingAgreementTransactionsScenario
 *
 * @package Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios
 */
class BillingAgreementTransactionsScenario implements IQuarkPaymentScenario {
	/**
	 * @var QuarkDTO $_response
	 */
	private $_response;

	/**
	 * @var string $_id = ''
	 */
	private $_id = '';

	/**
	 * @var QuarkDate $_start
	 */
	private $_start;

	/**
	 * @var QuarkDate $_end
	 */
	private $_end;

	/**
	 * @param string $id = ''
	 * @param QuarkDate $start = null
	 * @param QuarkDate $end = null
	 */
	public function __construct ($id = '', QuarkDate $start = null, QuarkDate $end = null) {
		$this->Id($id);
		$this->Start($start);
		$this->End($end);
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
	 * @param QuarkDate $start = ''
	 *
	 * @return string
	 */
	public function Start (QuarkDate $start = null) {
		if (func_num_args() != 0)
			$this->_start = $start;

		return $this->_start = $start;
	}

	/**
	 * @param QuarkDate $end = ''
	 *
	 * @return string
	 */
	public function End (QuarkDate $end = null) {
		if (func_num_args() != 0)
			$this->_end = $end;

		return $this->_end = $end;
	}

	/**
	 * @param IQuarkPaymentProvider|PayPal $provider
	 * @param IQuarkPaymentInstrument $instrument = null
	 *
	 * @return bool
	 */
	public function Proceed (IQuarkPaymentProvider $provider, IQuarkPaymentInstrument $instrument = null) {
		$query = array();

		if ($this->_start != null)
			$query['start_date'] = $this->_start->Date();

		if ($this->_end != null)
			$query['end_date'] = $this->_end->Date();

		$this->_response = $provider->API(
			QuarkDTO::METHOD_GET,
			QuarkURI::BuildQuery('/v1/payments/billing-agreements/' . $this->_id . '/transactions', $query, true)
		);

		return isset($this->_response->agreement_transaction_list);
	}

	/**
	 * @return QuarkDTO
	 */
	public function Response () {
		return $this->_response;
	}
}