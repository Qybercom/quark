<?php
namespace Quark\Extensions\Payment\Providers\Fondy\PaymentScenarios;

use Quark\Quark;
use Quark\QuarkDTO;

use Quark\Extensions\Payment\IQuarkPaymentInstrument;
use Quark\Extensions\Payment\IQuarkPaymentProvider;
use Quark\Extensions\Payment\IQuarkPaymentScenario;

use Quark\Extensions\Payment\Payment;
use Quark\Extensions\Payment\Providers\Fondy\Fondy;

/**
 * Class P2PCredit
 *
 * @package Quark\Extensions\Payment\Providers\Fondy\PaymentScenarios
 */
class P2PCredit implements IQuarkPaymentScenario {
	/**
	 * @var QuarkDTO $_response
	 */
	private $_response;

	/**
	 * @var string $_to
	 */
	private $_to;

	/**
	 * @var string $_order
	 */
	private $_order;

	/**
	 * @var float $_value = 0.0
	 */
	private $_value = 0.0;

	/**
	 * @var string $_currency = Payment::CURRENCY_RUR
	 */
	private $_currency = Payment::CURRENCY_RUR;

	/**
	 * @var string $_description = ''
	 */
	private $_description = '';

	/**
	 * @param string $description
	 * @param string $to
	 * @param string $currency = Payment::CURRENCY_RUB
	 * @param float $value = 0.0
	 * @param string $order = ''
	 */
	public function __construct ($description, $to, $currency = Payment::CURRENCY_RUB, $value = 0.0, $order = '') {
		$this->Description($description);
		$this->To($to);
		$this->Money($currency, $value);
		$this->Order(func_num_args() != 5 ? Quark::GuID() : $order);
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
	 * @param string $order = ''
	 *
	 * @return string
	 */
	public function Order ($order = '') {
		if (func_num_args() != 0)
			$this->_order = $order;

		return $this->_order;
	}

	/**
	 * @param string $currency = Payment::CURRENCY_RUR
	 * @param float $value = 0.0
	 *
	 * @return P2PCredit
	 */
	public function Money ($currency = Payment::CURRENCY_RUR, $value = 0.0) {
		$this->_currency = $currency;
		$this->_value = $value;

		return $this;
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
	 * @param IQuarkPaymentProvider|Fondy $provider
	 * @param IQuarkPaymentInstrument $instrument = null
	 *
	 * @return bool
	 */
	public function Proceed (IQuarkPaymentProvider $provider, IQuarkPaymentInstrument $instrument = null) {
		$this->_response = $provider->API('p2pcredit', array(
			'request' => array(
				'order_id' => $this->_order,
				'order_desc' => $this->_description,
				'currency' => $this->_currency,
				'amount' => $this->_value,
				'receiver_card_number' => $this->_to
			)
		));
		
		return $provider->ResponseOK($this->_response, Fondy::ORDER_APPROVED);
	}

	/**
	 * @return QuarkDTO
	 */
	public function Response () {
		return $this->_response;
	}
}