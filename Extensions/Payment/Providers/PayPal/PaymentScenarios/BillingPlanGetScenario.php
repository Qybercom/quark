<?php
namespace Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios;

use Quark\QuarkDTO;

use Quark\Extensions\Payment\IQuarkPaymentInstrument;
use Quark\Extensions\Payment\IQuarkPaymentProvider;
use Quark\Extensions\Payment\IQuarkPaymentScenario;

use Quark\Extensions\Payment\Providers\PayPal\PayPal;

/**
 * Class BillingPlanGetScenario
 *
 * @package Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios
 */
class BillingPlanGetScenario implements IQuarkPaymentScenario {
	/**
	 * @var string $_id = ''
	 */
	private $_id = '';

	/**
	 * @var QuarkDTO $_response
	 */
	private $_response;

	/**
	 * @param string $id = ''
	 */
	public function __construct ($id = '') {
		$this->Id($id);
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
	 * @param IQuarkPaymentProvider|PayPal $provider
	 * @param IQuarkPaymentInstrument $instrument = null
	 *
	 * @return bool
	 */
	public function Proceed (IQuarkPaymentProvider $provider, IQuarkPaymentInstrument $instrument = null) {
		$this->_response = $provider->API(QuarkDTO::METHOD_GET, '/v1/payments/billing-plans/' . $this->_id);

		return isset($this->_response->id) ? $this->_response : null;
	}

	/**
	 * @return QuarkDTO
	 */
	public function Response () {
		return $this->_response;
	}
}