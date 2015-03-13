<?php
namespace Quark\Extensions\Payment\CloudPayments\PaymentScenarios;

use Quark\Extensions\Payment\IPaymentScenario;
use Quark\Extensions\Payment\Payment;

use Quark\QuarkClient;
use Quark\QuarkDTO;
use Quark\QuarkHTTPTransport;
use Quark\QuarkJSONIOProcessor;

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
 * @package Quark\Extensions\Payment\CloudPayments\PaymentScenarios
 */
class TokenChargeScenario implements IPaymentScenario {
	/**
	 * @param $currency
	 * @param $amount
	 *
	 * @return bool
	 */
	public function Pay ($currency, $amount) {
		$this->Currency = $currency;
		$this->Amount = $amount;

		$request = QuarkDTO::ForPOST(new QuarkJSONIOProcessor());
		$request->Data($this);

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		$http = new QuarkClient('https://api.cloudpayments.ru/payments/tokens/charge', new QuarkHTTPTransport($request, $response));

		$http->Action();
	}
}