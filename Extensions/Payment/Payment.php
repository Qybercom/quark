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
	/**
	 * @var IPaymentScenario
	 */
	private $_scenario;

	/**
	 * @param IPaymentScenario $scenario
	 */
	public function __construct (IPaymentScenario $scenario) {
		$this->_scenario = $scenario;
	}

	public function Pay () {
		$user = 'pk_99d30b422ee37453692a9c95c521f';
		$pass = 'f6efa738f6e569fdfbe5e75fd1193a1d';

		$request = QuarkDTO::ForGET(new QuarkJSONIOProcessor());
		$request->Header(
			QuarkDTO::HEADER_AUTHORIZATION,
			'Basic ' . base64_encode($user . ':' . $pass)
		);

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		$http = new QuarkClient('https://api.cloudpayments.ru/test', new QuarkHTTPTransport($request, $response));

		Quark::On(Quark::EVENT_CONNECTION_EXCEPTION, function ($e) {
			print_r($e);
		});
		print_r($http->Action());
	}
}