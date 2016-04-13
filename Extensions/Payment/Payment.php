<?php
namespace Quark\Extensions\Payment;

use Quark\IQuarkExtension;

use Quark\Quark;
use Quark\QuarkDTO;

/**
 * Class Payment
 *
 * @package Quark\Extensions\Payment
 */
class Payment implements IQuarkExtension {
	const CURRENCY_MDL = 'MDL';
	const CURRENCY_RUB = 'RUB';
	const CURRENCY_RUR = 'RUR';
	const CURRENCY_USD = 'USD';
	const CURRENCY_EUR = 'EUR';

	/**
	 * @var PaymentConfig $_config
	 */
	private $_config;

	/**
	 * @var IQuarkPaymentScenario $_scenario
	 */
	private $_scenario;

	/**
	 * @var IQuarkPaymentInstrument $_instrument
	 */
	private $_instrument;

	/**
	 * @param string $config
	 * @param IQuarkPaymentScenario $scenario = null
	 * @param IQuarkPaymentInstrument $instrument = null
	 */
	public function __construct ($config, IQuarkPaymentScenario $scenario = null, IQuarkPaymentInstrument $instrument = null) {
		$this->_config = Quark::Config()->Extension($config);

		$this->_scenario = $scenario;
		$this->_instrument = $instrument;
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
	 * @return PaymentConfig
	 */
	public function &Config () {
		return $this->_config;
	}

	/**
	 * @param IQuarkPaymentScenario $scenario = null
	 *
	 * @return IQuarkPaymentScenario
	 */
	public function &Scenario (IQuarkPaymentScenario $scenario = null) {
		if (func_num_args() != 0)
			$this->_scenario = $scenario;

		return $this->_scenario;
	}

	/**
	 * @param IQuarkPaymentInstrument $instrument = null
	 *
	 * @return IQuarkPaymentInstrument
	 */
	public function &Instrument (IQuarkPaymentInstrument $instrument = null) {
		if (func_num_args() != 0)
			$this->_instrument = $instrument;

		return $this->_instrument;
	}

	/**
	 * @return bool
	 */
	public function Proceed () {
		return $this->_scenario->Proceed($this->_config->PaymentProvider(), $this->_instrument);
	}

	/**
	 * @return QuarkDTO
	 */
	public function Response () {
		return $this->_scenario->Response();
	}
}