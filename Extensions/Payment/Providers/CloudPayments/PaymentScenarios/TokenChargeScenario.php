<?php
namespace Quark\Extensions\Payment\Providers\CloudPayments\PaymentScenarios;

use Quark\QuarkDTO;

use Quark\Extensions\Payment\IQuarkPaymentScenario;
use Quark\Extensions\Payment\IQuarkPaymentProvider;
use Quark\Extensions\Payment\IQuarkPaymentInstrument;

use Quark\Extensions\Payment\Payment;
use Quark\Extensions\Payment\Providers\CloudPayments\CloudPayments;

/**
 * Class TokenChargeScenario
 *
 * @property $Amount		Numeric	Обязательный	Сумма платежа
 * @property $Currency		String	Обязательный	Валюта: RUB/USD/EUR
 * @property $InvoiceId		String	Необязательный	Номер счета или заказа
 * @property $Description	String	Необязательный	Описание оплаты в свободной форме
 * @property $AccountId		String	Обязательный	Идентификатор пользователя
 * @property $Email			String	Необязательный	E-mail плательщика на который будет отправлена квитанция об оплате
 * @property $JsonData		Json	Необязательный	Произвольные данные
 * @property $Token			String	Обязательный	Токен
 *
 * @package Quark\Extensions\Payment\Providers\CloudPayments\PaymentScenarios
 */
class TokenChargeScenario implements IQuarkPaymentScenario {
	/**
	 * @var float $Amount = 0.0
	 */
	public $Amount = 0.0;

	/**
	 * @var string $Currency = Payment::CURRENCY_USD
	 */
	public $Currency = Payment::CURRENCY_USD;

	/**
	 * @var string $AccountId = ''
	 */
	public $AccountId = '';

	/**
	 * @var string $Token = ''
	 */
	public $Token = '';

	/**
	 * @var QuarkDTO $_response
	 */
	private $_response;

	/**
	 * @var object $_model
	 */
	private $_model;

	/**
	 * @param string $currency = Payment::CURRENCY_USD
	 * @param float $amount = 0.0
	 * @param string $token = ''
	 */
	public function __construct ($currency = Payment::CURRENCY_USD, $amount = 0.0, $token = '') {
		$this->Money($currency, $amount);
		$this->Token = $token;
	}

	/**
	 * @param string $currency = Payment::CURRENCY_USD
	 * @param float $amount = 0.0
	 *
	 * @return TokenChargeScenario
	 */
	public function Money ($currency = Payment::CURRENCY_USD, $amount = 0.0) {
		$this->Currency = $currency;
		$this->Amount = $amount;

		return $this;
	}

	/**
	 * @param IQuarkPaymentProvider|CloudPayments $provider
	 * @param IQuarkPaymentInstrument $instrument = null
	 *
	 * @return bool
	 */
	public function Proceed (IQuarkPaymentProvider $provider, IQuarkPaymentInstrument $instrument = null) {
		$this->AccountId = $provider->user;

		$this->_response = $provider->API($this, 'https://api.cloudpayments.ru/payments/tokens/charge');
		$this->_model = isset($this->_response->Model) ? $this->_response->Model : null;

		return isset($this->_response->Success) && $this->_response->Success;
	}

	/**
	 * @return QuarkDTO
	 */
	public function Response () {
		return $this->_response;
	}

	/**
	 * @return object
	 */
	public function Model () {
		return $this->_model;
	}
}