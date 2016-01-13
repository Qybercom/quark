<?php
namespace Quark\Extensions\Payment\Providers\PayPal\PaymentInstruments;

use Quark\Extensions\Payment\IQuarkPaymentInstrument;

/**
 * Class PayPalAccountInstrument
 *
 * @package Quark\Extensions\Payment\Providers\PayPal\PaymentInstruments
 */
class PayPalAccountInstrument implements IQuarkPaymentInstrument {
	/**
	 * @return array
	 */
	public function PaymentInstrument () {
		return array(
			'payment_method' => 'paypal'
		);
	}
}