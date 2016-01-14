<?php
namespace Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios;

use Quark\QuarkDTO;
use Quark\QuarkDate;

use Quark\Extensions\Payment\IQuarkPaymentInstrument;
use Quark\Extensions\Payment\IQuarkPaymentProvider;
use Quark\Extensions\Payment\IQuarkPaymentScenario;

use Quark\Extensions\Payment\Payment;
use Quark\Extensions\Payment\Providers\PayPal\PayPal;
use Quark\Extensions\Payment\Providers\PayPal\PaymentInstruments\CreditCardInstrument;
use Quark\Extensions\Payment\Providers\PayPal\PaymentInstruments\PayPalAccountInstrument;

/**
 * Class BillingAgreementCreateScenario
 *
 * @package Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios
 */
class BillingAgreementCreateScenario implements IQuarkPaymentScenario {
	/**
	 * @var QuarkDTO $_response
	 */
	private $_response;

	/**
	 * @var string $_plan = ''
	 */
	private $_plan = '';

	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var string $_description = ''
	 */
	private $_description = '';

	/**
	 * @var QuarkDate $_start
	 */
	private $_start;

	/**
	 * @var array $_charge = null
	 */
	private $_charge = null;

	/**
	 * @var array $_merchant = null
	 */
	private $_merchant = null;

	/**
	 * @var object $_links
	 */
	private $_links;

	/**
	 * @param string $plan = ''
	 * @param string $name = ''
	 * @param string $description = ''
	 * @param QuarkDate $start = null
	 */
	public function __construct ($plan = '', $name = '', $description = '', QuarkDate $start = null) {
		$this->Plan($plan);
		$this->Name($name);
		$this->Description($description);
		$this->Start($start);

		$this->_links = new \StdClass();
	}

	/**
	 * @param string $plan = ''
	 *
	 * @return string
	 */
	public function Plan ($plan = '') {
		if (func_num_args() != 0)
			$this->_plan = $plan;

		return $this->_plan = $plan;
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
	 * @param string $id = ''
	 * @param string $currency = Payment::CURRENCY_USD
	 * @param float $value = 0.0
	 *
	 * @return array
	 */
	public function &ChargeModel ($id = '', $currency = Payment::CURRENCY_USD, $value = 0.0) {
		if (func_num_args() != 0)
			$this->_charge = array(
				'charge_id' => $id,
				'amount' => array(
					'currency' => $currency,
					'value' => $value
				)
			);

		return $this->_charge;
	}

	/**
	 * @param string $setupCurrency = Payment::CURRENCY_USD
	 * @param float $setupValue = 0.0
	 * @param string $return = ''
	 * @param string $cancel = ''
	 *
	 * @return array
	 */
	public function &MerchantPreferences ($setupCurrency = Payment::CURRENCY_USD, $setupValue = 0.0, $return = '', $cancel = '') {
		if (func_num_args() != 0)
			$this->_merchant = array(
				'cancel_url' => $cancel,
				'return_url' => $return,
				'setup_fee' => array(
					'currency' => $setupCurrency,
					'value' => $setupValue
				)
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
			'start_date' => $this->_start->DateTime(),
			'plan' => array('id' => $this->_plan),
			'payer' => $instrument->PaymentInstrument(),
		);

		if ($this->_charge != null)
			$request['override_charge_models'] = $this->_charge;

		if ($this->_merchant != null)
			$request['override_merchant_preferences'] = $this->_merchant;

		$this->_response = $provider->API(
			QuarkDTO::METHOD_POST,
			'/v1/payments/billing-agreements',
			$request
		);

		$state = $instrument instanceof PayPalAccountInstrument ? 'PENDING' : 'ACTIVE';

		if (!isset($this->_response->state) || $this->_response->state != $state) return false;
		if (!isset($this->_response->links) || !is_array($this->_response->links)) return false;

		foreach ($this->_response->links as $link)
			if (isset($link->rel) && isset($link->href))
				$this->_links->{$link->rel} = $link->href;

		return
			($instrument instanceof PayPalAccountInstrument && isset($this->_links->approval_url)) ||
			($instrument instanceof CreditCardInstrument && isset($this->_links->self));
	}

	/**
	 * @return QuarkDTO
	 */
	public function Response () {
		return $this->_response;
	}

	/**
	 * @return QuarkDTO
	 */
	public function Approve () {
		return isset($this->_links->approval_url)
			? QuarkDTO::ForRedirect($this->_links->approval_url)
			: null;
	}
}