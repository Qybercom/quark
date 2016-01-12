<?php
namespace Quark\Extensions\Payment\Providers\PayPal;

use Quark\IQuarkExtension;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;

use Quark\Extensions\Payment\IQuarkPaymentConfig;

/**
 * Class PayPalConfig
 *
 * @package Quark\Extensions\Payment\Providers\PayPal
 */
class PayPalConfig implements IQuarkPaymentConfig {
	const SCOPE_OPENID = 'openid';
	const SCOPE_PROFILE = 'profile';
	const SCOPE_EMAIL = 'email';
	const SCOPE_PHONE = 'phone';
	const SCOPE_ADDRESS = 'address';
	const SCOPE_ACCOUNT = 'https://uri.paypal.com/services/paypalattributes';

	public $user;
	public $pass;
	public $live = true;

	public $token = '';

	public $amount;
	public $currency;

	/**
	 * @param string $user
	 * @param string $password
	 * @param bool $live = true
	 */
	public function __construct ($user, $password, $live = true) {
		$this->user = $user;
		$this->pass = $password;
		$this->live = $live;
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
	public function AuthorizationForToken () {
		return 'Basic ' . base64_encode($this->user . ':' . $this->pass);
	}

	/**
	 * @return string
	 */
	public function AuthorizationForCall () {
		return 'Bearer ' . $this->token;
	}

	/**
	 * @param string $redirect = ''
	 * @param string[] $scope = [self::SCOPE_OPENID]
	 * @param string $state = ''
	 * @param string $country = ''
	 *
	 * @return string
	 */
	public function AuthorizationEndpoint ($redirect, $scope = [self::SCOPE_OPENID], $state = '', $country = '') {
		return ($this->live
			? 'https://www.paypal.com/' . (func_num_args() != 0 ? $country . '/' : '') . 'webapps/auth/protocol/openidconnect/v1/authorize'
			: 'https://www.sandbox.paypal.com/webapps/auth/protocol/openidconnect/v1/authorize')
			. '?' . http_build_query(array(
						'client_id' => $this->user,
						'response_type' => 'code',
						'redirect_uri' => $redirect,
						'scope' => implode(' ', $scope),
						'nonce' => Quark::GuID(),
						'state' => $state
					));
	}

	public function API ($data, $method, $url) {
		if ($this->token == '') {
			$request = QuarkDTO::ForGET(new QuarkJSONIOProcessor());
			$request->Header(QuarkDTO::HEADER_AUTHORIZATION, $this->AuthorizationForToken());
			$request->Data(array(
				'grant_type' => 'client_credentials'
			));

			$response = new QuarkDTO(new QuarkJSONIOProcessor());

			$token = QuarkHTTPClient::To($url, $request, $response);

			if (!isset($token->access_token)) return false;

			$this->token = $token->access_token;
		}

		$request = new QuarkDTO(new QuarkJSONIOProcessor());
		$request->Method($method);
		$request->Header(QuarkDTO::HEADER_AUTHORIZATION, $this->AuthorizationForCall());
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
}