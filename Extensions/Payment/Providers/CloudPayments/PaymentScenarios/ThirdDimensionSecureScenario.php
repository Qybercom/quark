<?php
namespace Quark\Extensions\Payment\Providers\CloudPayments\PaymentScenarios;

use Quark\QuarkDTO;

use Quark\Extensions\Payment\IQuarkPaymentConfig;
use Quark\Extensions\Payment\IQuarkPaymentScenario;

use Quark\Extensions\Payment\Providers\CloudPayments\CloudPaymentsConfig;

/**
 * Class ThirdDimensionSecureScenario
 *
 * @property $TransactionId	Int		Обязательный	Значение параметра MD
 * @property $PaRes			String	Обязательный	Значение одноименного параметра
 *
 * @package Quark\Extensions\Payment\Providers\CloudPayments\PaymentScenarios
 */
class ThirdDimensionSecureScenario implements IQuarkPaymentScenario {
	public $TransactionId = -1;
	public $PaRes = '';

	private $_response;

	/**
	 * @param string $id
	 * @param int $paRes
	 */
	public function __construct ($id, $paRes) {
		$this->TransactionId = $id;
		$this->PaRes = $paRes;
	}

	/**
	 * @param IQuarkPaymentConfig|CloudPaymentsConfig $config
	 *
	 * @return bool
	 */
	public function Pay (IQuarkPaymentConfig $config) {
		$this->_response = $config->API($this, 'https://api.cloudpayments.ru/payments/cards/post3ds')->Action();

		return isset($this->_response->Success) && $this->_response->Success;
	}

	/**
	 * @return QuarkDTO
	 */
	public function Response () {
		return $this->_response;
	}
}