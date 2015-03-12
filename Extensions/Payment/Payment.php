<?php
namespace Quark\Extensions\Payment;

use Quark\IQuarkExtension;
use Quark\Quark;
use Quark\QuarkClient;
use Quark\QuarkDTO;
use Quark\QuarkHTTPTransport;
use Quark\QuarkJSONIOProcessor;

/**
 * Class Payment
 *
 * @package Quark\Extensions\Payment
 */
class Payment implements IQuarkExtension {
	const CURRENCY_MDL = 'MDL';
	const CURRENCY_RUB = 'RUB';
	const CURRENCY_USD = 'USD';
	const CURRENCY_EUR = 'EUR';

	/**
	 * @var IPaymentScenario $_scenario
	 */
	private $_scenario;

	/**
	 * @var object $_payload
	 */
	private $_payload;

	/**
	 * @param string $provider
	 * @param IPaymentScenario $scenario
	 */
	public function __construct ($provider, IPaymentScenario $scenario) {
		$this->_scenario = $scenario;

		$fields = $this->_scenario->Fields();

		if (!Quark::isAssociative($fields))
			$fields = new \StdClass();

		$this->_payload = Quark::Normalize(new \StdClass(), $fields);
	}

	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	public function &__get ($key) {
		return $this->_scenario->$key;
	}

	/**
	 * @param $key
	 * @param $value
	 */
	public function __set ($key, $value) {
		$this->_scenario->$key = $value;
	}

	/**
	 * @param $key
	 *
	 * @return bool
	 */
	public function __isset ($key) {
		return isset($this->_scenario->$key);
	}

	/**
	 * @param string $currency
	 * @param float $amount
	 *
	 * @return mixed
	 */
	public function Pay ($currency, $amount) {
		$user = 'pk_99d30b422ee37453692a9c95c521f';
		$pass = 'f6efa738f6e569fdfbe5e75fd1193a1d';

		$request = QuarkDTO::ForGET(new QuarkJSONIOProcessor());
		$request->Header(
			QuarkDTO::HEADER_AUTHORIZATION,
			'Basic ' . base64_encode($user . ':' . $pass)
		);

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		$http = new QuarkClient('https://api.cloudpayments.ru/test', new QuarkHTTPTransport($request, $response));
		$http->ip = false;

		return $http->Action();
	}
}