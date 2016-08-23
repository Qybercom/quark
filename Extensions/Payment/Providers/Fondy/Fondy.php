<?php
namespace Quark\Extensions\Payment\Providers\Fondy;

use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;

use Quark\Extensions\Payment\IQuarkPaymentProvider;

/**
 * Class Fondy
 *
 * @package Quark\Extensions\Payment\Providers\Fondy
 */
class Fondy implements IQuarkPaymentProvider {
	const API_ENDPOINT = 'https://api.fondy.eu/api/';

	const RESPONSE_SUCCESS = 'success';
	const RESPONSE_FAILURE = 'failure';

	const ORDER_CREATED = 'created';
	const ORDER_PROCESSING = 'processing';
	const ORDER_DECLINED = 'declined';
	const ORDER_APPROVED = 'approved';
	const ORDER_EXPIRED = 'expired';
	const ORDER_REVERSED = 'reversed';

	const TEST_MERCHANT_ID = '1396424';
	const TEST_MERCHANT_PASSWORD = 'test';

	const TEST_CARD_SUCCESS_3DS = '4444555566661111';
	const TEST_CARD_FAILURE_3DS = '4444111166665555';
	const TEST_CARD_SUCCESS = '4444555511116666';
	const TEST_CARD_FAILURE = '4444111155556666';

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
	 * @param object $ini
	 *
	 * @return mixed
	 */
	public function PaymentProviderApplication ($appId, $appSecret, $ini) {
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
		$data['request']['merchant_id'] = $this->appId;
		$data['request']['signature'] = $this->Signature($data['request']);

		$request = QuarkDTO::ForPOST(new QuarkJSONIOProcessor());
		$request->Data($data);

		return QuarkHTTPClient::To(self::API_ENDPOINT . $method, $request, new QuarkDTO(new QuarkJSONIOProcessor()));
	}

	/**
	 * @param $data = []
	 *
	 * @return string
	 */
	public function Signature ($data = []) {
		if (key_exists('signature', $data))
			unset($data['signature']);

		if (key_exists('response_signature_string', $data))
			unset($data['response_signature_string']);

		$i = 0;
		$sign = $this->appSecret;
		$keys = array_keys($data);

		sort($keys);
		$size = sizeof($keys);

		while ($i < $size) {
			if ($data[$keys[$i]] != '')
				$sign .= '|' . $data[$keys[$i]];

			$i++;
		}

		return sha1($sign);
	}

	/**
	 * @param QuarkDTO|bool $response
	 * @param string $orderStatus = ''
	 *
	 * @return bool
	 */
	public function ResponseOK ($response, $orderStatus = '') {
		return
			$response &&
			$response instanceof QuarkDTO &&
			isset($response->response) &&
			isset($response->response->response_status) &&
			$response->response->response_status == self::RESPONSE_SUCCESS &&
			(func_num_args() == 2
				? isset($response->response->order_status) && $response->response->order_status == $orderStatus
				: true
			) &&
			isset($response->response->signature) &&
			$response->response->signature == $this->Signature((array)$response->response);
	}
}