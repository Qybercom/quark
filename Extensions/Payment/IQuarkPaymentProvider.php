<?php
namespace Quark\Extensions\Payment;

/**
 * Interface IQuarkPaymentProvider
 *
 * @package Quark\Extensions\Payment
 */
interface IQuarkPaymentProvider {
	/**
	 * @param string $appId
	 * @param string $appSecret
	 *
	 * @return mixed
	 */
	public function PaymentProviderApplication($appId, $appSecret);
}