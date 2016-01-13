<?php
namespace Quark\Extensions\Payment\Providers\PayPal\PaymentInstruments;

use Quark\Extensions\Payment\IQuarkPaymentInstrument;

/**
 * Class CreditCardInstrument
 *
 * @package Quark\Extensions\Payment\Providers\PayPal\PaymentInstruments
 */
class CreditCardInstrument implements IQuarkPaymentInstrument {
	const TYPE_VISA = 'visa';

	const COUNTRY_US = 'US';
	const COUNTRY_MD = 'MD';
	const COUNTRY_RU = 'RU';
	const COUNTRY_GB = 'GB';
	const COUNTRY_CA = 'CA';

	/**
	 * @var string|int $number = ''
	 */
	public $number = '';

	/**
	 * @var string $type = self::TYPE_VISA
	 */
	public $type = self::TYPE_VISA;

	/**
	 * @var string|int $expire_month = ''
	 */
	public $expire_month = '';

	/**
	 * @var string|int $expire_year = ''
	 */
	public $expire_year = '';

	/**
	 * @var string|int $cvv2 = ''
	 */
	public $cvv2 = '';

	/**
	 * @var string $first_name = ''
	 */
	public $first_name = '';

	/**
	 * @var string $last_name = ''
	 */
	public $last_name = '';

	/**
	 * @var string $billing_address = ''
	 */
	public $billing_address = '';

	/**
	 * @param string|int $number
	 * @param string|int $cvv2
	 * @param string $type = self::TYPE_VISA
	 */
	public function __construct ($number, $cvv2, $type = self::TYPE_VISA) {
		$this->number = $number;
		$this->cvv2 = $cvv2;
		$this->type = $type;
	}

	/**
	 * @param string|int $month
	 * @param string|int $year
	 *
	 * @return CreditCardInstrument
	 */
	public function Expiration ($month, $year) {
		$this->expire_month = $month;
		$this->expire_year = $year;

		return $this;
	}

	/**
	 * @param string $first
	 * @param string $last
	 *
	 * @return CreditCardInstrument
	 */
	public function Name ($first, $last) {
		$this->first_name = $first;
		$this->last_name = $last;

		return $this;
	}

	/**
	 * @param string $country = ''
	 * @param string $state = ''
	 * @param string $city = ''
	 * @param string|int $postal = ''
	 * @param string $line1 = ''
	 *
	 * @return CreditCardInstrument
	 */
	public function BillingAddress ($country = '', $state = '', $city = '', $postal = '', $line1 = '') {
		$this->billing_address = array(
			'line1' => $line1,
			'city' => $city,
			'state' => $state,
			'postal_code' => $postal,
			'country_code' => $country
		);

		return $this;
	}

	/**
	 * @return array
	 */
	public function PaymentInstrument () {
		return array(
			'payment_method' => 'credit_card',
			'funding_instruments' => array($this)
		);
	}
}