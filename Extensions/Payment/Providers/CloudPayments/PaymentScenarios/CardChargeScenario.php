<?php
namespace Quark\Extensions\Payment\Providers\CloudPayments\PaymentScenarios;

use Quark\Quark;
use Quark\QuarkClient;
use Quark\QuarkDTO;
use Quark\QuarkHTTPTransportClient;
use Quark\QuarkJSONIOProcessor;

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
	 */
	public function __construct ($name, $cryptogram) {
		$this->Name = $name;
		$this->CardCryptogramPacket = $cryptogram;
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

		$request = QuarkDTO::ForPOST(new QuarkJSONIOProcessor());
		$request->Header(
			QuarkDTO::HEADER_AUTHORIZATION,
			'Basic ' . base64_encode($config->user . ':' . $config->pass)
		);
		$request->Data($this);

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		$http = new QuarkClient('https://api.cloudpayments.ru/payments/cards/charge', new QuarkHTTPTransportClient($request, $response));
		$http->ip = false;

		$this->_response = $http->Action();

		if (!isset($this->_response->Success) || !$this->_response->Success) {
			Quark::Log(print_r($http, true));
			return false;
		}

		return true;
	}

	/**
	 * @return QuarkDTO
	 */
	public function Response () {
		return $this->_response;
	}
}