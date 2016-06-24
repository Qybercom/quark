<?php
namespace Quark\Extensions\Payment\Providers\Payeer;

use Quark\Extensions\Payment\IQuarkPaymentProvider;

/**
 * Class Payeer
 *
 * @package Quark\Extensions\Payment\Providers\Payeer
 */
class Payeer implements IQuarkPaymentProvider {
	const API_ENDPOINT = '';

	/**
	 * @param string $appId
	 * @param string $appSecret
	 *
	 * @return mixed
	 */
	public function PaymentProviderApplication ($appId, $appSecret) {
		// TODO: Implement PaymentProviderApplication() method.
	}
}