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
	const TYPE_MASTERCARD = 'mastercard';

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
	 * @var string $billing_address = []
	 */
	public $billing_address = null;

	/**
	 * @param string $type = self::TYPE_VISA
	 * @param string|int $number = ''
	 * @param string|int $cvv2 = ''
	 * @param string|int $month = ''
	 * @param string|int $year = ''
	 * @param string $first_name = ''
	 * @param string $last_name = ''
	 */
	public function __construct ($type = self::TYPE_VISA, $number = '', $cvv2 = '', $month = '', $year = '', $first_name = '', $last_name = '') {
		$this->number = $number;
		$this->cvv2 = $cvv2;
		$this->type = $type;
	}

	/**
	 * @param string|int $month = ''
	 * @param string|int $year = ''
	 *
	 * @return CreditCardInstrument
	 */
	public function Expiration ($month = '', $year = '') {
		$this->expire_month = $month;
		$this->expire_year = $year;

		return $this;
	}

	/**
	 * @param string $first_name = ''
	 * @param string $last_name = ''
	 *
	 * @return CreditCardInstrument
	 */
	public function Holder ($first_name = '', $last_name = '') {
		$this->first_name = $first_name;
		$this->last_name = $last_name;

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
		if ($this->billing_address == null)
			unset($this->billing_address);

		return array(
			'payment_method' => 'credit_card',
			'funding_instruments' => array(
				array('credit_card' => $this)
			)
		);
	}
}