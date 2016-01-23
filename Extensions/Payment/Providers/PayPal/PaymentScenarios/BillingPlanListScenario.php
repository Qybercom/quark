<?php
namespace Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios;

use Quark\QuarkDTO;

use Quark\Extensions\Payment\IQuarkPaymentInstrument;
use Quark\Extensions\Payment\IQuarkPaymentProvider;
use Quark\Extensions\Payment\IQuarkPaymentScenario;

use Quark\Extensions\Payment\Providers\PayPal\PayPal;

/**
 * Class BillingPlanListScenario
 *
 * @package Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios
 */
class BillingPlanListScenario implements IQuarkPaymentScenario {
	/**
	 * @var QuarkDTO $_response
	 */
	private $_response;

	/**
	 * @var int $_page = 0
	 */
	private $_page = 0;

	/**
	 * @var string $_state = PayPal::BILLING_STATE_CREATED
	 */
	private $_state = PayPal::BILLING_STATE_CREATED;

	/**
	 * @var int $_page_size = 10
	 */
	private $_page_size = 10;

	/**
	 * @var bool $_total = true
	 */
	private $_total = true;

	/**
	 * @param int $page = 0
	 * @param string $state = PayPal::BILLING_STATE_CREATED
	 * @param int $page_size = 10
	 * @param bool $total = true
	 */
	public function __construct ($page = 0, $state = PayPal::BILLING_STATE_CREATED, $page_size = 10, $total = true) {
		$this->Page($page);
		$this->State($state);
		$this->PageSize($page_size);
		$this->Total($total);
	}

	/**
	 * @param int $page = 0
	 *
	 * @return int
	 */
	public function Page ($page = 0) {
		if (func_num_args() != 0)
			$this->_page = $page;

		return $this->_page;
	}

	/**
	 * @param string $state = PayPal::BILLING_STATE_CREATED
	 *
	 * @return string
	 */
	public function State ($state = PayPal::BILLING_STATE_CREATED) {
		if (func_num_args() != 0)
			$this->_state = $state;

		return $this->_state;
	}

	/**
	 * @param int $page_size = 10
	 *
	 * @return int
	 */
	public function PageSize ($page_size = 10) {
		if (func_num_args() != 0)
			$this->_page_size = $page_size;

		return $this->_page_size;
	}

	/**
	 * @param bool $total = true
	 *
	 * @return bool
	 */
	public function Total ($total = true) {
		if (func_num_args() != 0)
			$this->_total = $total;

		return $this->_total;
	}

	/**
	 * @param IQuarkPaymentProvider|PayPal $provider
	 * @param IQuarkPaymentInstrument $instrument = null
	 *
	 * @return bool
	 */
	public function Proceed (IQuarkPaymentProvider $provider, IQuarkPaymentInstrument $instrument = null) {
		$this->_response = $provider->API(
			QuarkDTO::METHOD_GET,
			'/v1/payments/billing-plans'
			. '?page=' . $this->_page
			. '&status=' . $this->_state
			. '&page_size=' . $this->_page_size
			. ($this->_total ? '&total_required=yes' : '')
		);

		return isset($this->_response->plans) ? $this->_response : null;
	}

	/**
	 * @return QuarkDTO
	 */
	public function Response () {
		return $this->_response;
	}
}