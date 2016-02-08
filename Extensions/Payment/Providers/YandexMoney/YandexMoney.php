<?php
namespace Quark\Extensions\Payment\Providers\YandexMoney;

use Quark\Extensions\Payment\IQuarkPaymentProvider;
use Quark\QuarkDTO;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;

/**
 * Class YandexMoney
 *
 * @package Quark\Extensions\Payment\Providers\YandexMoney
 */
class YandexMoney implements IQuarkPaymentProvider {
	const SCOPE_ACCOUNT_INFO = 'account-info';
	const SCOPE_OPERATION_HISTORY = 'operation-history';
	const SCOPE_OPERATION_DETAILS = 'operation-details';
	const SCOPE_INCOMING_TRANSFERS = 'incoming-transfers';
	const SCOPE_PAYMENT = 'payment';
	const SCOPE_PAYMENT_SHOP = 'payment-shop';
	const SCOPE_PAYMENT_P2P = 'payment-p2p';
	const SCOPE_MONEY_SOURCE = 'money-source';

	/**
	 * @var string $appId = ''
	 */
	public $appId = '';

	/**
	 * @var string $appSecret = ''
	 */
	public $appSecret = '';

	/**
	 * @var bool $live = true
	 */
	public $live = true;

	/**
	 * @param bool $live = true
	 */
	public function __construct ($live = true) {
		$this->live = $live;
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
	 * @param $redirect
	 * @param array $scope
	 *
	 * @return string
	 */
	public function ApplicationAuthorize ($redirect, $scope = []) {
		$form = new QuarkFormIOProcessor();
		$params = $form->Encode(array(
			'response_type' => 'code',
			'redirect_uri' => $redirect,
			'client_id' => $this->appId,
			'scope' => implode(' ', $scope)
		));

		return 'https://money.yandex.ru/oauth/token?' . $params;

		/*$request = QuarkDTO::ForPOST();
		$request->Protocol(QuarkDTO::HTTP_VERSION_1_1);
		$request->Data(array(
			'response_type' => 'code',
			'redirect_uri' => $redirect,
			'client_id' => $this->appId,
			'scope' => implode(' ', $scope)
		));

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		$token = QuarkHTTPClient::To('https://money.yandex.ru/oauth/token', $request, $response);

		if (!isset($token->access_token)) return false;

		$this->token = $token->access_token;

		return true;*/
	}

	/**
	 * @return bool
	 */
	public function ApplicationToken ($redirect, $code) {
		$request = QuarkDTO::ForPOST(new QuarkFormIOProcessor());
		$request->Protocol(QuarkDTO::HTTP_VERSION_1_1);
		$request->Data(array(
			'grant_type' => 'authorization_code',
			'client_id' => $this->appId,
			'client_secret' => $this->appSecret,
			'redirect_uri' => $redirect,
			'code' => $code
		));

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		$token = QuarkHTTPClient::To('https://money.yandex.ru/oauth/token', $request, $response);

		if (!isset($token->access_token)) return false;

		$this->token = $token->access_token;

		return true;
	}
}