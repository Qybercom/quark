<?php
namespace Quark\Extensions\Payment\Providers\CloudPayments\PaymentScenarios;

use Quark\QuarkDTO;

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

	private $_response;

	/**
	 * @param string $token
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

		$this->_response = $config->API($this, 'https://api.cloudpayments.ru/payments/tokens/charge');

		return isset($this->_response->Success) && $this->_response->Success;
	}

	/**
	 * @return QuarkDTO
	 */
	public function Response () {
		return $this->_response;
	}
}