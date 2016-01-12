<?php
namespace Quark\Extensions\Payment\Providers\CloudPayments;

use Quark\IQuarkExtension;
use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;

use Quark\Extensions\Payment\IQuarkPaymentConfig;

/**
 * Class CloudPaymentsConfig
 *
 * @package Quark\Extensions\Payment\Providers
 */
class CloudPaymentsConfig implements IQuarkPaymentConfig {
	public $user;
	public $pass;

	public $amount;
	public $currency;

	/**
	 * @param string $user
	 * @param string $password
	 */
	public function __construct ($user, $password) {
		$this->user = $user;
		$this->pass = $password;
	}

	/**
	 * @param string $currency
	 * @param float $amount
	 *
	 * @return mixed
	 */
	public function Money ($currency, $amount) {
		$this->currency = $currency;
		$this->amount = $amount;
	}

	/**
	 * @return string
	 */
	public function Authorization () {
		return 'Basic ' . base64_encode($this->user . ':' . $this->pass);
	}

	/**
	 * @param \Quark\Extensions\Payment\IQuarkPaymentScenario $data
	 * @param string $url
	 *
	 * @return QuarkHTTPClient
	 */
	public function API ($data, $url) {
		$request = QuarkDTO::ForPOST(new QuarkJSONIOProcessor());
		$request->Header(QuarkDTO::HEADER_AUTHORIZATION, $this->Authorization());
		$request->Data($data);

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		return QuarkHTTPClient::To($url, $request, $response);
	}

	/**
	 * @param string $name
	 */
	public function Stacked ($name) {
		// TODO: Implement Stacked() method.
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		// TODO: Implement ExtensionInstance() method.
	}

	/**
	 * @param string $redirect
	 * @param string[] $scope
	 *
	 * @return string
	 */
	public function AuthorizationEndpoint ($redirect, $scope) {
		// TODO: Implement AuthorizationEndpoint() method.
	}
}