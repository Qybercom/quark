<?php
namespace Quark\Extensions\Payment\Providers\CloudPayments\PaymentScenarios;

use Quark\QuarkDTO;

use Quark\Extensions\Payment\IQuarkPaymentProvider;
use Quark\Extensions\Payment\IQuarkPaymentScenario;
use Quark\Extensions\Payment\IQuarkPaymentInstrument;

use Quark\Extensions\Payment\Providers\CloudPayments\CloudPayments;

/**
 * Class ThirdDimensionSecureScenario
 *
 * @property $TransactionId	Int		Обязательный	Значение параметра MD
 * @property $PaRes			String	Обязательный	Значение одноименного параметра
 *
 * @package Quark\Extensions\Payment\Providers\CloudPayments\PaymentScenarios
 */
class ThirdDimensionSecureScenario implements IQuarkPaymentScenario {
	/**
	 * @var int $TransactionId = -1
	 */
	public $TransactionId = -1;

	/**
	 * @var string $PaRes = ''
	 */
	public $PaRes = '';

	/**
	 * @var QuarkDTO $_response
	 */
	private $_response;

	/**
	 * @param string $id = ''
	 * @param string $paRes = ''
	 */
	public function __construct ($id = '', $paRes = '') {
		$this->TransactionId = $id;
		$this->PaRes = $paRes;
	}

	/**
	 * @param IQuarkPaymentProvider|CloudPayments $provider
	 * @param IQuarkPaymentInstrument $instrument = null
	 *
	 * @return bool
	 */
	public function Proceed (IQuarkPaymentProvider $provider, IQuarkPaymentInstrument $instrument = null) {
		$this->_response = $provider->API($this, 'https://api.cloudpayments.ru/payments/cards/post3ds');

		return isset($this->_response->Success) && $this->_response->Success;
	}

	/**
	 * @return QuarkDTO
	 */
	public function Response () {
		return $this->_response;
	}
}