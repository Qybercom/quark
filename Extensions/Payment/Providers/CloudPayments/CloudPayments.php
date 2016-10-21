<?php
namespace Quark\Extensions\Payment\Providers\CloudPayments;

use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;

use Quark\Extensions\Payment\IQuarkPaymentProvider;

/**
 * Class CloudPayments
 *
 * @package Quark\Extensions\Payment\Providers
 */
class CloudPayments implements IQuarkPaymentProvider {
	/**
	 * @var string $user = ''
	 */
	public $user = '';

	/**
	 * @var string $pass = ''
	 */
	public $pass = '';

	/**
	 * @param string $appId
	 * @param string $appSecret
	 * @param object $ini
	 *
	 * @return void
	 */
	public function PaymentProviderApplication ($appId, $appSecret, $ini) {
		$this->user = $appId;
		$this->pass = $appSecret;
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
	 * @return QuarkDTO
	 */
	public function API ($data, $url) {
		$request = QuarkDTO::ForPOST(new QuarkJSONIOProcessor());
		$request->Header(QuarkDTO::HEADER_AUTHORIZATION, $this->Authorization());
		$request->Data($data);

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		return QuarkHTTPClient::To($url, $request, $response);
	}
}