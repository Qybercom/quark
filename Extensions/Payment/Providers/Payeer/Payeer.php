<?php
namespace Quark\Extensions\Payment\Providers\Payeer;

use Quark\QuarkDTO;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;

use Quark\Extensions\Payment\IQuarkPaymentProvider;

/**
 * Class Payeer
 *
 * @package Quark\Extensions\Payment\Providers\Payeer
 */
class Payeer implements IQuarkPaymentProvider {
	const API_ENDPOINT = 'https://payeer.com/ajax/api/api.php';

	/**
	 * @var string $account = ''
	 */
	public $account = '';

	/**
	 * @var string $appId = ''
	 */
	public $appId = '';

	/**
	 * @var string $appSecret = ''
	 */
	public $appSecret = '';

	/**
	 * @param string $account = ''
	 */
	public function __construct ($account = '') {
		$this->account = $account;
	}

	/**
	 * @param string $appId
	 * @param string $appSecret
	 *
	 * @return mixed
	 */
	public function PaymentProviderApplication ($appId, $appSecret) {
		$this->appId = $appId;
		$this->appSecret = $appSecret;
	}

	/**
	 * @param $method
	 * @param $data = []
	 *
	 * @return bool|QuarkDTO
	 */
	public function API ($method, $data = []) {
		$data['account'] = $this->account;
		$data['apiId'] = $this->appId;
		$data['apiPass'] = $this->appSecret;
		$data['action'] = $method;

		$request = QuarkDTO::ForPOST(new QuarkFormIOProcessor());
		$request->Data($data);

		return QuarkHTTPClient::To(self::API_ENDPOINT, $request, new QuarkDTO(new QuarkJSONIOProcessor()));
	}

	/**
	 * @param QuarkDTO|bool $response
	 *
	 * @return bool
	 */
	public function ResponseOK ($response) {
		return $response != false && $response->auth_error == '0' && !$response->errors;
	}
}