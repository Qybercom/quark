<?php
namespace Quark\Extensions\OAuth;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkObject;

/**
 * Class OAuthConsumerBehavior
 *
 * @package Quark\Extensions\OAuth
 */
trait OAuthConsumerBehavior {
	/**
	 * @var OAuthConfig $_config
	 */
	private $_config;

	/**
	 * @var IQuarkOAuthProvider $_provider
	 */
	private $_provider;

	/**
	 * @var OAuthToken $_token
	 */
	private $_token;

	/**
	 * @param string $config
	 *
	 * @return mixed
	 */
	public function OAuthConfig ($config) {
		$this->_config = Quark::Config()->Extension($config);
		$this->_provider = $this->_config->Provider();
	}

	/**
	 * @param IQuarkOAuthProvider $provider
	 *
	 * @return mixed
	 */
	public function OAuthProvider (IQuarkOAuthProvider $provider) {
		$this->_provider = $provider;
	}

	/**
	 * @param OAuthToken $token
	 *
	 * @return mixed
	 */
	public function OAuthToken (OAuthToken $token) {
		$this->_token = $token;
	}

	/**
	 * @param string $redirect
	 * @param string[] $scope = []
	 *
	 * @return string
	 */
	public function OAuthLoginURL ($redirect, $scope = []) {
		return $this->_provider->OAuthLoginURL($redirect, $scope);
	}

	/**
	 * @param string $redirect
	 *
	 * @return string
	 */
	public function OAuthLogoutURL ($redirect) {
		return $this->_provider->OAuthLogoutURL($redirect);
	}

	/**
	 * @param string $url = ''
	 * @param QuarkDTO $request = null
	 * @param QuarkDTO $response = null
	 *
	 * @return mixed
	 */
	public function OAuthAPI ($url = '', QuarkDTO $request = null, QuarkDTO $response = null) {
		try {
			return $this->_provider->OAuthAPI($url, $request, $response);
		}
		catch (OAuthAPIException $e) {
			Quark::Log('[' . QuarkObject::ClassOf($this) . '.' . QuarkObject::ClassOf($this->_provider) . '] API error:');

			Quark::Trace($e->Request());
			Quark::Trace($e->Response());

			return null;
		}
	}
}