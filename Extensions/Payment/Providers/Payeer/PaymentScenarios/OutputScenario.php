<?php
namespace Quark\Extensions\Payment\Providers\Payeer\PaymentScenarios;

use Quark\QuarkDTO;

use Quark\Extensions\Payment\IQuarkPaymentInstrument;
use Quark\Extensions\Payment\IQuarkPaymentProvider;
use Quark\Extensions\Payment\IQuarkPaymentScenario;

use Quark\Extensions\Payment\Payment;
use Quark\Extensions\Payment\Providers\Payeer\Payeer;

/**
 * Class OutputScenario
 *
 * @package Quark\Extensions\Payment\Providers\Payeer\PaymentScenarios
 */
class OutputScenario implements IQuarkPaymentScenario {
	/**
	 * @var QuarkDTO $_response
	 */
	private $_response;

	/**
	 * @var string $_system = ''
	 */
	private $_system = '';

	/**
	 * @var string $_to
	 */
	private $_to;

	/**
	 * @var float $_value = 0.0
	 */
	private $_value = 0.0;

	/**
	 * @var string $_currency = Payment::CURRENCY_RUB
	 */
	private $_currency = Payment::CURRENCY_RUB;

	/**
	 * @var string $_currencyOut = Payment::CURRENCY_RUB
	 */
	private $_currencyOut = Payment::CURRENCY_RUB;

	/**
	 * @param string $system
	 * @param string $to
	 * @param string $currency = Payment::CURRENCY_RUB
	 * @param float $value = 0.0
	 * @param string $currencyOut = Payment::CURRENCY_RUB
	 */
	public function __construct ($system, $to, $currency = Payment::CURRENCY_RUB, $value = 0.0, $currencyOut = Payment::CURRENCY_RUB) {
		$this->PaymentSystem($system);
		$this->To($to);
		$this->Money($currency, $value);
		$this->CurrencyOut($currencyOut);
	}

	/**
	 * @param string $system = ''
	 *
	 * @return string
	 */
	public function PaymentSystem ($system = '') {
		if (func_num_args() != 0)
			$this->_system = $system;

		return $this->_system;
	}

	/**
	 * @param string $to = ''
	 *
	 * @return string
	 */
	public function To ($to = '') {
		if (func_num_args() != 0)
			$this->_to = $to;

		return $this->_to;
	}

	/**
	 * @param string $currency = Payment::CURRENCY_RUB
	 * @param float $value = 0.0
	 *
	 * @return OutputScenario
	 */
	public function Money ($currency = Payment::CURRENCY_RUB, $value = 0.0) {
		$this->_currency = $currency;
		$this->_value = $value;

		return $this;
	}

	/**
	 * @param string $currency = Payment::CURRENCY_RUB
	 *
	 * @return string
	 */
	public function CurrencyOut ($currency = Payment::CURRENCY_RUB) {
		if (func_num_args() != 0)
			$this->_currencyOut = $currency;

		return $this->_currencyOut;
	}

	/**
	 * @param IQuarkPaymentProvider|Payeer $provider
	 * @param IQuarkPaymentInstrument $instrument = null
	 *
	 * @return bool
	 */
	public function Proceed (IQuarkPaymentProvider $provider, IQuarkPaymentInstrument $instrument = null) {
		$this->_response = $provider->API('output', array(
			'ps' => $this->_system,
			'sumIn' => $this->_value,
			'curIn' => $this->_currency,
			'curOut' => $this->_currencyOut,
			'param_ACCOUNT_NUMBER' => $this->_to
		));

		return $provider->ResponseOK($this->_response);
	}

	/**
	 * @return QuarkDTO
	 */
	public function Response () {
		return $this->_response;
	}
}