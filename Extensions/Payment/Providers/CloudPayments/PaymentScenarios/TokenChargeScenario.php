<?php
namespace Quark\Extensions\Payment\Providers\CloudPayments\PaymentScenarios;

use Quark\QuarkClient;
use Quark\QuarkDTO;
use Quark\QuarkHTTPTransport;
use Quark\QuarkJSONIOProcessor;

use Quark\Extensions\Payment\IQuarkPaymentScenario;
use Quark\Extensions\Payment\IQuarkPaymentConfig;

use Quark\Extensions\Payment\Providers\CloudPayments\CloudPaymentsConfig;

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
	public $Token = '';

	/**
	 * @param $token
	 */
	public function __construct ($token) {
		$this->Token = $token;
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

		$http = new QuarkClient('https://api.cloudpayments.ru/payments/tokens/charge', new QuarkHTTPTransport($request, $response));
		$http->ip = false;

		return $http->Action();
	}
}