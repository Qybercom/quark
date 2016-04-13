<?php
namespace Quark\Extensions\Payment\Providers\OooPay\PaymentScenarios;

use Quark\QuarkDTO;

use Quark\Extensions\Payment\IQuarkPaymentProvider;
use Quark\Extensions\Payment\IQuarkPaymentScenario;
use Quark\Extensions\Payment\IQuarkPaymentInstrument;

use Quark\Extensions\Payment\Payment;
use Quark\Extensions\Payment\PaymentConfig;

use Quark\Extensions\Payment\Providers\OooPay\OooPay;

/**
 * Class CashOutScenario
 *
 * @package Quark\Extensions\Payment\Providers\OooPay\PaymentScenarios
 */
class CashOutScenario implements IQuarkPaymentScenario {
	const METHOD_PAYEER_RUR = 114;
	const METHOD_PAYEER_USD = 115;
	const METHOD_BITCOIN = 116;
	const METHOD_OOOPAY_EUR = 109;
	const METHOD_OOOPAY_USD = 87;
	const METHOD_WEBMONEY_WMR = 1;
	const METHOD_WEBMONEY_WMZ = 2;
	const METHOD_QIWI = 63;
	const METHOD_YANDEX_MONEY = 45;
	const METHOD_PERFECT_MONEY_EUR = 69;
	const METHOD_PERFECT_MONEY_USD = 64;
	const METHOD_MOBILE_MEGAFON_STOLICNHIY = 82;
	const METHOD_MOBILE_MEGAFON_SEVERO_ZAPADNIY = 137;
	const METHOD_MOBILE_MEGAFON_SIBIRSKIY = 138;
	const METHOD_MOBILE_MEGAFON_KAVKAZKIY = 139;
	const METHOD_MOBILE_MEGAFON_POVOLJSKIY = 140;
	const METHOD_MOBILE_MEGAFON_URALISKIY = 141;
	const METHOD_MOBILE_MEGAFON_DALINEVOSTOCHNIY = 142;
	const METHOD_MOBILE_MEGAFON_CENTRALINIY = 143;
	const METHOD_MOBILE_BEELINE = 83;
	const METHOD_MOBILE_MTS = 84;
	const METHOD_MOBILE_TELE2 = 132;
	const METHOD_CREDIT_CARD = 94;
	const METHOD_CREDIT_CARD_UAH = 67;
	const METHOD_PAYPAL = 70;

	const STATUS_PAYED = 'PAYED';
	const STATUS_CANCELLED = 'CANCELED';

	/**
	 * @var QuarkDTO $_response
	 */
	private $_response;

	/**
	 * @var string $_method
	 */
	private $_method;

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
	 * @param string $method
	 * @param string $to
	 * @param string $order
	 * @param string $currency = Payment::CURRENCY_RUR
	 * @param float $value = 0.0
	 * @param string $description = ''
	 */
	public function __construct ($method, $to, $order, $currency = Payment::CURRENCY_RUR, $value = 0.0, $description = '') {
		$this->Method($method);
		$this->To($to);
		$this->Order($order);
		$this->Money($currency, $value);
		$this->Description($description);
	}

	/**
	 * @param string $method = ''
	 *
	 * @return string
	 */
	public function Method ($method = '') {
		if (func_num_args() != 0)
			$this->_method = $method;

		return $this->_method;
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
	 * @return CashOutScenario
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
	 * @param IQuarkPaymentProvider|OooPay $provider
	 * @param IQuarkPaymentInstrument $instrument = null
	 *
	 * @return bool
	 */
	public function Proceed (IQuarkPaymentProvider $provider, IQuarkPaymentInstrument $instrument = null) {
		$this->_response = $provider->API(
			'cash_out',
			array(
				'order_id' => $this->_order,
				'amount' => $this->_value,
				'cur_id' => $this->_method,
				'to' => $this->_to,
				'description' => $this->_description,
				'currency' => $this->_currency
			),
			array('order_id', 'amount', 'cur_id', 'to', 'description')
		);

		return $this->_response->error == 0 && $this->_response->answer->order_id == $this->_order;
	}

	/**
	 * @return QuarkDTO
	 */
	public function Response () {
		return $this->_response;
	}

	/**
	 * @param string $config
	 * @param QuarkDTO $request
	 *
	 * @return bool
	 */
	public static function IsSucceeded ($config, QuarkDTO $request) {
		$payment = PaymentConfig::Instance($config);

		return $payment instanceof OooPay
			? $request->status == self::STATUS_PAYED && $request->sign == md5($payment->appId . $request->payment_id . $request->status . $payment->appSecret)
			: false;
	}
}