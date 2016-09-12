<?php
namespace Quark\Extensions\Payment\Providers\CloudPayments\PaymentScenarios;

use Quark\QuarkDTO;

use Quark\Extensions\Payment\IQuarkPaymentScenario;
use Quark\Extensions\Payment\IQuarkPaymentProvider;
use Quark\Extensions\Payment\IQuarkPaymentInstrument;

use Quark\Extensions\Payment\Payment;
use Quark\Extensions\Payment\Providers\CloudPayments\CloudPayments;

/**
 * Class CardChargeScenario
 *
 * @property $Amount				Numeric	Обязательный	Сумма платежа
 * @property $Currency				String	Обязательный	Валюта: RUB/USD/EUR
 * @property $InvoiceId				String	Необязательный	Номер счета или заказа
 * @property $Description			String	Необязательный	Описание оплаты в свободной форме
 * @property $IpAddress				String	Обязательный	IP адрес плательщика
 * @property $AccountId				String	Необязательный	Идентификатор пользователя
 * @property $Email					String	Необязательный	E-mail плательщика на который будет отправлена квитанция об оплате
 * @property $JsonData				Json	Необязательный	Произвольные данные
 * @property $Name					String	Обязательный	Имя держателя карты в латинице
 * @property $CardCryptogramPacket	String	Обязательный	Криптограмма карточных данных
 *
 * @package Quark\Extensions\Payment\Providers\CloudPayments\PaymentScenarios
 */
class CardChargeScenario implements IQuarkPaymentScenario {
	/**
	 * @var float $Amount = 0.0
	 */
	public $Amount = 0.0;

	/**
	 * @var string $Currency = Payment::CURRENCY_USD
	 */
	public $Currency = Payment::CURRENCY_USD;

	/**
	 * @var string $Name = ''
	 */
	public $Name = '';

	/**
	 * @var string $IpAddress
	 */
	public $IpAddress = '';

	/**
	 * @var string $AccountId = ''
	 */
	public $AccountId = '';

	/**
	 * @var string $CardCryptogramPacket = ''
	 */
	public $CardCryptogramPacket = '';

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
	 * @param string $name = ''
	 * @param string $cryptogram = ''
	 * @param string $ip = ''
	 */
	public function __construct ($currency = Payment::CURRENCY_USD, $amount = 0.0, $name = '', $cryptogram = '', $ip = '') {
		$this->Money($currency, $amount);
		$this->Name = $name;
		$this->CardCryptogramPacket = $cryptogram;
		$this->IpAddress = $ip;
	}

	/**
	 * @param string $currency = Payment::CURRENCY_USD
	 * @param float $amount = 0.0
	 *
	 * @return CardChargeScenario
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
		if (!($provider instanceof CloudPayments)) return false;

		$this->AccountId = $provider->user;

		$this->_response = $provider->API($this, 'https://api.cloudpayments.ru/payments/cards/charge');
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
		return isset($this->_model->AcsUrl) ? $this->_model : (object)array('CardHolderMessage' => $this->_response->Message);
	}

	/**
	 * @return bool
	 */
	public function Need3DSecure () {
		return isset($this->_model->AcsUrl);
	}
}