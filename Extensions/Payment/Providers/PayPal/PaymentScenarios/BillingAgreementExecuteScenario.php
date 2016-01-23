<?php
namespace Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios;

use Quark\QuarkDTO;

use Quark\Extensions\Payment\IQuarkPaymentInstrument;
use Quark\Extensions\Payment\IQuarkPaymentProvider;
use Quark\Extensions\Payment\IQuarkPaymentScenario;

use Quark\Extensions\Payment\Providers\PayPal\PayPal;

/**
 * Class BillingAgreementExecuteScenario
 *
 * @package Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios
 */
class BillingAgreementExecuteScenario implements IQuarkPaymentScenario {
	/**
	 * @var QuarkDTO $_response
	 */
	private $_response;

	/**
	 * @var string $_token = ''
	 */
	private $_token = '';

	/**
	 * @param string $token = ''
	 */
	public function __construct ($token = '') {
		$this->Token($token);
	}

	/**
	 * @param string $token = ''
	 *
	 * @return string
	 */
	public function Token ($token = '') {
		if (func_num_args() != 0)
			$this->_token = $token;

		return $this->_token;
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
			'/v1/payments/billing-agreements/' . $this->_token . '/agreement-execute'
		);

		return isset($this->_response->id);
	}

	/**
	 * @return QuarkDTO
	 */
	public function Response () {
		return $this->_response;
	}

	/**
	 * @param QuarkDTO $request
	 *
	 * @return PaymentExecuteScenario
	 */
	public static function FromRedirect (QuarkDTO $request) {
		return new self($request->token);
	}
}