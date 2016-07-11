<?php
namespace Quark\Extensions\Payment;

/**
 * Class PaymentFinancialTransaction
 *
 * @package Quark\Extensions\Payment
 */
class PaymentFinancialTransaction {
	/**
	 * @var float $_amount = 0.0
	 */
	private $_amount = 0.0;

	/**
	 * @var string $_currency = Payment::CURRENCY_USD
	 */
	private $_currency = Payment::CURRENCY_USD;

	/**
	 * @var string $_actor = ''
	 */
	private $_actor = '';

	/**
	 * @var string $_provider = ''
	 */
	private $_provider = '';

	/**
	 * @param float $amount = 0.0
	 * @param string $currency = Payment::CURRENCY_USD
	 * @param string $actor = ''
	 * @param string $provider = ''
	 */
	public function __construct ($amount = 0.0, $currency = Payment::CURRENCY_USD, $actor = '', $provider = '') {
		$this->Amount($amount);
		$this->Currency($currency);
		$this->Actor($actor);
		$this->Provider($provider);
	}

	/**
	 * @return string
	 */
	public function __toString () {
		return $this->Summary();
	}

	/**
	 * @return string
	 */
	public function Summary () {
		return $this->_amount . ':' . $this->_currency . ' <-> ' . $this->_actor . '[' . $this->_provider . ']';
	}

	/**
	 * @param float $amount = 0.0
	 *
	 * @return float
	 */
	public function Amount ($amount = 0.0) {
		if (func_num_args() != 0)
			$this->_amount = $amount;

		return $this->_amount;
	}

	/**
	 * @param string $currency = Payment::CURRENCY_USD
	 *
	 * @return string
	 */
	public function Currency ($currency = Payment::CURRENCY_USD) {
		if (func_num_args() != 0)
			$this->_currency = $currency;

		return $this->_currency;
	}

	/**
	 * @param string $actor = ''
	 *
	 * @return string
	 */
	public function Actor ($actor = '') {
		if (func_num_args() != 0)
			$this->_actor = $actor;

		return $this->_actor;
	}

	/**
	 * @param string $provider = ''
	 *
	 * @return string
	 */
	public function Provider ($provider = '') {
		if (func_num_args() != 0)
			$this->_provider = $provider;

		return $this->_provider;
	}
}