<?php
namespace Quark\Extensions\Payment;

use Quark\IQuarkExtension;

use Quark\Quark;

/**
 * Class Payment
 *
 * @package Quark\Extensions\Payment
 */
class Payment implements IQuarkExtension {
	const CURRENCY_MDL = 'MDL';
	const CURRENCY_RUB = 'RUB';
	const CURRENCY_USD = 'USD';
	const CURRENCY_EUR = 'EUR';

	/**
	 * @var IQuarkPaymentConfig $_config
	 */
	private $_config;

	/**
	 * @var IQuarkPaymentScenario $_scenario
	 */
	private $_scenario;

	/**
	 * @param string $config
	 * @param IQuarkPaymentScenario $scenario
	 */
	public function __construct ($config, IQuarkPaymentScenario $scenario) {
		$this->_config = Quark::Config()->Extension($config);
		$this->_scenario = $scenario;
	}

	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	public function &__get ($key) {
		return $this->_scenario->$key;
	}

	/**
	 * @param $key
	 * @param $value
	 */
	public function __set ($key, $value) {
		$this->_scenario->$key = $value;
	}

	/**
	 * @param $key
	 *
	 * @return bool
	 */
	public function __isset ($key) {
		return isset($this->_scenario->$key);
	}

	/**
	 * @param string $currency
	 * @param float $amount
	 *
	 * @return mixed
	 */
	public function Pay ($currency, $amount) {
		if ($this->_config == null) return false;

		$this->_config->Money($currency, $amount);

		return $this->_scenario->Pay($this->_config);
	}
}