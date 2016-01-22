<?php
namespace Quark\Extensions\Payment\Providers\PayPal;

/**
 * Class PayPalBilling
 *
 * @package Quark\Extensions\Payment\Providers\PayPal
 */
class PayPalBilling {
	const STATE_CREATED = 'CREATED';
	const STATE_ACTIVE = 'ACTIVE';
	const STATE_INACTIVE = 'INACTIVE';

	const TYPE_DURATION_FIXED = 'FIXED';
	const TYPE_DURATION_INFINITE = 'INFINITE';

	const TYPE_PLAN_TRIAL = 'TRIAL';
	const TYPE_PLAN_REGULAR = 'REGULAR';

	const TYPE_CHARGE_TAX = 'TAX';
	const TYPE_CHARGE_SHIPPING = 'SHIPPING';

	const FREQUENCY_MONTH = 'MONTH';
	const FREQUENCY_YEAR = 'YEAR';

	const FAIL_AMOUNT_CONTINUE = 'CONTINUE';
	const FAIL_AMOUNT_CANCEL = 'CANCEL';
}