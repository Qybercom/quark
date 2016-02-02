<?php
namespace Quark\Extensions\Payment\Providers\YandexMoney\PaymentScenarios;

use Quark\QuarkDTO;

use Quark\Extensions\Payment\IQuarkPaymentInstrument;
use Quark\Extensions\Payment\IQuarkPaymentProvider;
use Quark\Extensions\Payment\IQuarkPaymentScenario;

/**
 * Class PaymentRequest
 *
 * @package Quark\Extensions\Payment\Providers\YandexMoney\PaymentScenarios
 */
class PaymentRequest implements IQuarkPaymentScenario {
	/**
	 * @var string $_redirect = ''
	 */
	private $_redirect = '';

	public function __construct () {

	}

	/**
	 * @param string $uri = ''
	 *
	 * @return string
	 */
	public function RedirectURI ($uri = '') {
		if (func_num_args() != 0)
			$this->_redirect = $uri;

		return $this->_redirect;
	}

	/**
	 * @param IQuarkPaymentProvider $provider
	 * @param IQuarkPaymentInstrument $instrument = null
	 *
	 * @return bool
	 */
	public function Proceed (IQuarkPaymentProvider $provider, IQuarkPaymentInstrument $instrument = null) {
		// TODO: Implement Proceed() method.
	}

	/**
	 * @return QuarkDTO
	 */
	public function Response () {
		// TODO: Implement Response() method.
	}
}