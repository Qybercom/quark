<?php
namespace Quark\Extensions\OAuth\Flows;

use Quark\QuarkDTO;
use Quark\QuarkJSONIOProcessor;

use Quark\Extensions\OAuth\IQuarkOAuthFlow;
use Quark\Extensions\OAuth\OAuthConfig;
use Quark\Extensions\OAuth\OAuthError;
use Quark\Extensions\OAuth\OAuthFlowBehavior;
use Quark\Extensions\OAuth\OAuthToken;

/**
 * Class DeviceCodeFlow
 *
 * @package Quark\Extensions\OAuth\Flows
 */
class DeviceCodeFlow implements IQuarkOAuthFlow {
	use OAuthFlowBehavior;

	/**
	 * @var bool $_stageAuthorize = false
	 */
	private $_stageAuthorize = false;

	/**
	 * @var bool $_stageToken = false
	 */
	private $_stageToken = false;

	/**
	 * @var string $_code
	 */
	private $_code = '';

	/**
	 * @param QuarkDTO $request
	 *
	 * @return bool
	 */
	public function OAuthFlowRecognize (QuarkDTO $request) {
		$url = OAuthConfig::URLAllowed($request);
		if (!$url) return false;

		$this->_stageAuthorize = $url == OAuthConfig::URL_TOKEN
			&& isset($request->response_type)
			&& $request->response_type == OAuthConfig::RESPONSE_DEVICE_CODE;

		$this->_stageToken = $url == OAuthConfig::URL_TOKEN
			&& isset($request->grant_type)
			&& $request->grant_type == OAuthConfig::GRANT_DEVICE_CODE;

		$this->_oAuthFlowInit($request);

		$this->_code = $request->code;

		return $this->_stageAuthorize || $this->_stageToken;
	}

	/**
	 * @return bool
	 */
	public function OAuthFlowRequiresAuthentication () {
		return $this->_stageAuthorize;
	}

	/**
	 * @param OAuthToken $token
	 *
	 * @return QuarkDTO|OAuthError
	 */
	public function OAuthFlowSuccess (OAuthToken $token) {
		if ($this->_stageAuthorize) {
			$response = QuarkDTO::ForResponse(new QuarkJSONIOProcessor());
			$response->Data(array(
				'verification_uri' => $token->verification_uri,
				'user_code' => $token->user_code,
				'device_code' => $token->device_code,
				'interval' => $token->interval
			));

			return $response;
		}

		if ($this->_stageToken) {
			$response = QuarkDTO::ForResponse(new QuarkJSONIOProcessor());
			$response->Data($token->ExtractOAuth());

			return $response;
		}

		return null;
	}

	/**
	 * @return string
	 */
	public function OAuthFlowModelProcessMethod () {
		return 'OAuthFlowDeviceCode';
	}
}