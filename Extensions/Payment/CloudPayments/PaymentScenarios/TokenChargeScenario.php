<?php
namespace Quark\Extensions\Payment\CloudPayments\PaymentScenarios;

use Quark\Extensions\Payment\IPaymentScenario;

/**
 * Class TokenChargeScenario
 *
 * @package Quark\Extensions\Payment\CloudPayments\PaymentScenarios
 */
class TokenChargeScenario implements IPaymentScenario {
	/**
	 * @return string
	 */
	public function URL () {
		return 'https://api.cloudpayments.ru/payments/tokens/charge';
	}

	/**
	 * @return array
	 */
	public function Fields () {
		return array(
			'Amount', //	Numeric	Обязательный	Сумма платежа
			'Currency', //	String	Обязательный	Валюта: RUB/USD/EUR
			'InvoiceId', //	String	Необязательный	Номер счета или заказа
			'Description', //	String	Необязательный	Описание оплаты в свободной форме
			'AccountId', //	String	Обязательный	Идентификатор пользователя
			'Email', //	String	Необязательный	E-mail плательщика на который будет отправлена квитанция об оплате
			'JsonData', //	Json	Необязательный	Произвольные данные
			'Token', //	String	Обязательный	Токен
		);
	}
}

/*
 *
Amount	Numeric	Обязательный	Сумма платежа
Currency	String	Обязательный	Валюта: RUB/USD/EUR
InvoiceId	String	Необязательный	Номер счета или заказа
Description	String	Необязательный	Описание оплаты в свободной форме
AccountId	String	Обязательный	Идентификатор пользователя
Email	String	Необязательный	E-mail плательщика на который будет отправлена квитанция об оплате
JsonData	Json	Необязательный	Произвольные данные
Token	String	Обязательный	Токен
 */