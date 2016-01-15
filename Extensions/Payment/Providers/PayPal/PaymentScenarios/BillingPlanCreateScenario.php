<?php
namespace Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios;

use Quark\QuarkDTO;

use Quark\Extensions\Payment\IQuarkPaymentInstrument;
use Quark\Extensions\Payment\IQuarkPaymentProvider;
use Quark\Extensions\Payment\IQuarkPaymentScenario;

use Quark\Extensions\Payment\Payment;
use Quark\Extensions\Payment\Providers\PayPal\PayPal;
use Quark\Extensions\Payment\Providers\PayPal\PayPalBilling;

/**
 * Class BillingPlanCreateScenario
 *
 * @package Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios
 */
class BillingPlanCreateScenario implements IQuarkPaymentScenario {
	/**
	 * @var QuarkDTO $_response
	 */
	private $_response;

	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var string $_description = ''
	 */
	private $_description = '';

	/**
	 * @var string $_duration = PayPalBilling::TYPE_DURATION_INFINITE
	 */
	private $_duration = PayPalBilling::TYPE_DURATION_INFINITE;

	/**
	 * @var array $_periods = []
	 */
	private $_periods = array();

	/**
	 * @var array $_merchant = null
	 */
	private $_merchant = null;

	/**
	 * @var object $_links
	 */
	private $_links;

	/**
	 * @param string $name = ''
	 * @param string $description = ''
	 * @param string $duration = PayPalBilling::TYPE_DURATION_INFINITE
	 */
	public function __construct ($name = '', $description = '', $duration = PayPalBilling::TYPE_DURATION_INFINITE) {
		$this->Name($name);
		$this->Description($description);
		$this->Duration($duration);

		$this->_links = new \StdClass();
	}

	/**
	 * @param string $name = ''
	 *
	 * @return string
	 */
	public function Name ($name = '') {
		if (func_num_args() != 0)
			$this->_name = $name;

		return $this->_name = $name;
	}

	/**
	 * @param string $description = ''
	 *
	 * @return string
	 */
	public function Description ($description = '') {
		if (func_num_args() != 0)
			$this->_description = $description;

		return $this->_description = $description;
	}

	/**
	 * @param string $duration = ''
	 *
	 * @return string
	 */
	public function Duration ($duration = '') {
		if (func_num_args() != 0)
			$this->_duration = $duration;

		return $this->_duration = $duration;
	}

	/**
	 * @param string $name = ''
	 * @param string $currency = Payment::CURRENCY_USD
	 * @param float $amount = 0.0
	 * @param string $frequency = PayPalBilling::FREQUENCY_MONTH
	 * @param int $cycles = 0
	 * @param int $interval = 1
	 *
	 * @return BillingPlanCreateScenario
	 */
	public function TrialPeriod ($name = '', $currency = Payment::CURRENCY_USD, $amount = 0.0, $frequency = PayPalBilling::FREQUENCY_MONTH, $cycles = 0, $interval = 1) {
		$this->_periods[] = array(
			'name' => $name,
			'type' => PayPalBilling::TYPE_PLAN_TRIAL,
			'frequency' => $frequency,
			'frequency_interval' => $interval,
			'amount' => array(
				'value' => $amount,
				'currency' => $currency
			),
			'cycles' => $cycles,
			'charge_models' => array()
		);

		return $this;
	}

	/**
	 * @param string $name = ''
	 * @param string $currency = Payment::CURRENCY_USD
	 * @param float $amount = 0.0
	 * @param string $frequency = self::FREQUENCY_MONTH
	 * @param int $cycles = 0
	 * @param int $interval = 1
	 *
	 * @return BillingPlanCreateScenario
	 */
	public function RegularPeriod ($name = '', $currency = Payment::CURRENCY_USD, $amount = 0.0, $frequency = PayPalBilling::FREQUENCY_MONTH, $cycles = 0, $interval = 1) {
		$this->_periods[] = array(
			'name' => $name,
			'type' => PayPalBilling::TYPE_PLAN_REGULAR,
			'frequency' => $frequency,
			'frequency_interval' => $interval,
			'amount' => array(
				'value' => $amount,
				'currency' => $currency
			),
			'cycles' => $cycles,
			'charge_models' => array()
		);

		return $this;
	}

	/**
	 * @param string $setupCurrency = Payment::CURRENCY_USD
	 * @param float $setupValue = 0.0
	 * @param string $return = ''
	 * @param string $cancel = ''
	 * @param int $maxFailAttempts = 1
	 *
	 * @return array
	 */
	public function &MerchantPreferences ($setupCurrency = Payment::CURRENCY_USD, $setupValue = 0.0, $return = '', $cancel = '', $maxFailAttempts = 1) {
		if (func_num_args() != 0)
			$this->_merchant = array(
				'cancel_url' => $cancel,
				'return_url' => $return,
				'setup_fee' => array(
					'currency' => $setupCurrency,
					'value' => $setupValue
				),
				'max_fail_attempts' => $maxFailAttempts,
				'initial_fail_amount_action' => $maxFailAttempts == 0
					? PayPalBilling::FAIL_AMOUNT_CONTINUE
					: PayPalBilling::FAIL_AMOUNT_CANCEL
			);

		return $this->_merchant;
	}

	/**
	 * @param IQuarkPaymentProvider|PayPal $provider
	 * @param IQuarkPaymentInstrument $instrument = null
	 *
	 * @return bool
	 */
	public function Proceed (IQuarkPaymentProvider $provider, IQuarkPaymentInstrument $instrument = null) {
		$request = array(
			'name' => $this->_name,
			'description' => $this->_description,
			'type' => $this->_duration,
			'payment_definitions' => $this->_periods,
			'merchant_preferences' => $this->_merchant
		);

		$this->_response = $provider->API(
			QuarkDTO::METHOD_POST,
			'/v1/payments/billing-plans',
			$request
		);

		if (!isset($this->_response->state) || $this->_response->state != PayPalBilling::STATE_CREATED) return false;
		if (!isset($this->_response->links) || !is_array($this->_response->links)) return false;

		foreach ($this->_response->links as $link)
			if (isset($link->rel) && isset($link->href))
				$this->_links->{$link->rel} = $link->href;

		return isset($this->_links->self);
	}

	/**
	 * @return QuarkDTO
	 */
	public function Response () {
		return $this->_response;
	}
}