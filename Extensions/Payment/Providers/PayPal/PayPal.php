<?php
namespace Quark\Extensions\Payment\Providers\PayPal;

use Quark\QuarkURI;
use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkFormIOProcessor;

use Quark\Extensions\Payment\IQuarkPaymentProvider;

/**
 * Class PayPal
 *
 * @package Quark\Extensions\Payment\Providers\PayPal
 */
class PayPal implements IQuarkPaymentProvider {
	const SCOPE_OPENID = 'openid';
	const SCOPE_PROFILE = 'profile';
	const SCOPE_EMAIL = 'email';
	const SCOPE_PHONE = 'phone';
	const SCOPE_ADDRESS = 'address';
	const SCOPE_ACCOUNT = 'https://uri.paypal.com/services/paypalattributes';

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
	 * @var string $token = ''
	 */
	public $token = '';

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
	 * @return string
	 */
	public function AuthorizationForCall () {
		return 'Bearer ' . $this->token;
	}

	/**
	 * @return string
	 */
	public function AuthorizationForToken () {
		return 'Basic ' . base64_encode($this->appId . ':' . $this->appSecret);
	}

	/**
	 * @return bool
	 */
	public function ApplicationToken () {
		$request = QuarkDTO::ForPOST(new QuarkFormIOProcessor());
		$request->Header(QuarkDTO::HEADER_AUTHORIZATION, $this->AuthorizationForToken());
		$request->Data(array(
			'grant_type' => 'client_credentials'
		));

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		$token = QuarkHTTPClient::To('https://api.' . ($this->live ? '' : 'sandbox.') . 'paypal.com/v1/oauth2/token', $request, $response);

		if (!isset($token->access_token)) return false;

		$this->token = $token->access_token;

		return true;
	}

	/**
	 * @param $method
	 * @param $url
	 * @param $data
	 *
	 * @return bool|QuarkDTO
	 */
	public function API ($method, $url, $data) {
		if ($this->token == '' && !$this->ApplicationToken()) return false;

		$request = new QuarkDTO(new QuarkJSONIOProcessor());
		$request->URI(QuarkURI::FromURI('https://api.' . ($this->live ? '' : 'sandbox.') . 'paypal.com/' . $url, false));
		$request->Method($method);
		$request->Header(QuarkDTO::HEADER_AUTHORIZATION, $this->AuthorizationForCall());
		$request->Data($data);

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		return QuarkHTTPClient::To($request->URI()->URI(), $request, $response);
	}
}