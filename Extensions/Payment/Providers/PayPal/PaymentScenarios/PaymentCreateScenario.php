<?php
namespace Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios;

use Quark\QuarkDTO;

use Quark\Extensions\Payment\IQuarkPaymentProvider;
use Quark\Extensions\Payment\IQuarkPaymentScenario;
use Quark\Extensions\Payment\IQuarkPaymentInstrument;

use Quark\Extensions\Payment\Payment;
use Quark\Extensions\Payment\Providers\PayPal\PayPal;
use Quark\Extensions\Payment\Providers\PayPal\PaymentInstruments\CreditCardInstrument;
use Quark\Extensions\Payment\Providers\PayPal\PaymentInstruments\PayPalAccountInstrument;

/**
 * Class PaymentCreateScenario
 *
 * @package Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios
 */
class PaymentCreateScenario implements IQuarkPaymentScenario {
	/**
	 * @var QuarkDTO $_response
	 */
	private $_response;

	/**
	 * @var float $_value = 0.0
	 */
	private $_value = 0.0;

	/**
	 * @var string $_currency = Payment::CURRENCY_USD
	 */
	private $_currency = Payment::CURRENCY_USD;

	/**
	 * @var string $_return_url = '';
	 */
	private $_return_url = '';

	/**
	 * @var string $_cancel_url = ''
	 */
	private $_cancel_url = '';

	/**
	 * @var string $_description = ''
	 */
	private $_description = '';

	/**
	 * @var object $_links
	 */
	private $_links;

	/**
	 * @param string $currency = Payment::CURRENCY_USD
	 * @param float $value = 0.0
	 * @param string $return = ''
	 * @param string $cancel = ''
	 * @param string $description = ''
	 */
	public function __construct ($currency = Payment::CURRENCY_USD, $value = 0.0, $return = '', $cancel = '', $description = '') {
		$this->Money($currency, $value);
		$this->ReturnURL($return);
		$this->CancelURL($cancel);
		$this->Description($description);

		$this->_links = new \StdClass();
	}

	/**
	 * @param string $currency = Payment::CURRENCY_USD
	 * @param float $value = 0.0
	 *
	 * @return PaymentCreateScenario
	 */
	public function Money ($currency = Payment::CURRENCY_USD, $value = 0.0) {
		$this->_currency = $currency;
		$this->_value = $value;

		return $this;
	}

	/**
	 * @param string $url = ''
	 *
	 * @return string
	 */
	public function ReturnURL ($url = '') {
		if (func_num_args() != 0)
			$this->_return_url = $url;

		return $this->_return_url;
	}

	/**
	 * @param string $url = ''
	 *
	 * @return string
	 */
	public function CancelURL ($url = '') {
		if (func_num_args() != 0)
			$this->_cancel_url = $url;

		return $this->_cancel_url;
	}

	/**
	 * @param string $description = ''
	 *
	 * @return string
	 */
	public function Description ($description = '') {
		if (func_num_args() != 0)
			$this->_description = $description;

		return $this->_description;
	}

	/**
	 * @param IQuarkPaymentProvider|PayPal $provider
	 * @param IQuarkPaymentInstrument $instrument = null
	 *
	 * @return bool
	 */
	public function Proceed (IQuarkPaymentProvider $provider, IQuarkPaymentInstrument $instrument = null) {
		$request = array(
			'intent' => 'sale',
			'redirect_urls' => array(
				'return_url' => $this->_return_url,
				'cancel_url' => $this->_cancel_url
			),
			'payer' => $instrument->PaymentInstrument(),
			'transactions' => array(
				array(
					'amount' => array(
						'total' => (string)((float)$this->_value),
						'currency' => $this->_currency
					),
					'description' => $this->_description
				)
			)
		);

		$this->_response = $provider->API(
			QuarkDTO::METHOD_POST,
			'/v1/payments/payment',
			$request
		);

		$state = $instrument instanceof PayPalAccountInstrument ? 'created' : 'approved';

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