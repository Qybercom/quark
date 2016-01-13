<?php
namespace Quark\Extensions\Payment;

use Quark\QuarkDTO;

/**
 * Interface IQuarkPaymentScenario
 *
 * @package Quark\Extensions\Payment
 */
interface IQuarkPaymentScenario {
	/**
	 * @param IQuarkPaymentProvider $provider
	 * @param IQuarkPaymentInstrument $instrument = null
	 *
	 * @return bool
	 */
	public function Proceed(IQuarkPaymentProvider $provider, IQuarkPaymentInstrument $instrument = null);

	/**
	 * @return QuarkDTO
	 */
	public function Response();
}