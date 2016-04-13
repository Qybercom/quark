<?php
namespace Quark\Extensions\Payment\Providers\OooPay;

use Quark\Extensions\Payment\IQuarkPaymentProvider;
use Quark\QuarkDTO;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkHTTPClient;
use Quark\QuarkXMLIOProcessor;

/**
 * Class OooPay
 *
 * @package Quark\Extensions\Payment\Providers\OooPay
 */
class OooPay implements IQuarkPaymentProvider {
	const API_ENDPOINT = 'https://www.ooopay.org/cashin_v1.php';

	/**
	 * @var string $appId = ''
	 */
	public $appId = '';

	/**
	 * @var string $appSecret = ''
	 */
	public $appSecret = '';

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
	 * @param string $action
	 * @param array $data = []
	 * @param array $sign = []
	 *
	 * @return QuarkDTO|bool
	 */
	public function API ($action, $data = [], $sign = []) {
		$fields = '';

		foreach ($sign as $key)
			if (isset($data[$key]))
				$fields .= $data[$key];

		$data['action'] = $action;
		$data['sign'] = md5($this->appId . $fields . $this->appSecret);

		$request = QuarkDTO::ForPOST(new QuarkFormIOProcessor());
		$request->Data($data);

		$response = new QuarkDTO(new QuarkXMLIOProcessor());

		return QuarkHTTPClient::To('http://api.mycab/test', $request, $response);
	}
}