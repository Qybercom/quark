<?php
namespace Quark\Extensions\Payment\Providers\CloudPayments\PaymentScenarios;

use Quark\QuarkDTO;

use Quark\Extensions\Payment\IQuarkPaymentScenario;
use Quark\Extensions\Payment\IQuarkPaymentConfig;

use Quark\Extensions\Payment\Providers\CloudPayments\CloudPaymentsConfig;

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
	public $Name = '';
	public $CardCryptogramPacket = '';

	private $_response;

	/**
	 * @param string $name
	 * @param string $cryptogram
	 * @param string $ip
	 */
	public function __construct ($name, $cryptogram, $ip) {
		$this->Name = $name;
		$this->CardCryptogramPacket = $cryptogram;
		$this->IpAddress = $ip;
	}

	/**
	 * @param IQuarkPaymentConfig|CloudPaymentsConfig $config
	 *
	 * @return bool
	 */
	public function Pay (IQuarkPaymentConfig $config) {
		$this->Currency = $config->currency;
		$this->Amount = $config->amount;
		$this->AccountId = $config->user;

		$this->_response = $config->API($this, 'https://api.cloudpayments.ru/payments/cards/charge')->Action();

		return isset($this->_response->Success) && $this->_response->Success;
	}

	/**
	 * @return QuarkDTO
	 */
	public function Response () {
		return $this->_response;
	}
}