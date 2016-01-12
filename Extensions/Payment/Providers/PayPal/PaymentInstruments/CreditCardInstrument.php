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

	public $number;
	public $type = self::TYPE_VISA;
	public $expire_month;
	public $expire_year;
	public $cvv2;
	public $first_name;
	public $last_name;
	public $billing_address;

	/**
	 * @param string|int $number
	 * @param string|int $cvv2
	 * @param string $type
	 */
	public function __construct ($number, $cvv2, $type = self::TYPE_VISA) {
		$this->number = $number;
		$this->cvv2 = $cvv2;
		$this->type = $type;
	}

	/**
	 * @param int $month
	 * @param int $year
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
	 * @param $country
	 * @param $state
	 * @param $city
	 * @param $postal
	 * @param $line1
	 *
	 * @return CreditCardInstrument
	 */
	public function BillingAddress ($country, $state, $city, $postal, $line1) {
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
		return $this;
	}
}