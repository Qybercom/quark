<?php
namespace Quark\Extensions\SocialNetwork;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkModel;
use Quark\QuarkObject;

use Quark\Extensions\OAuth\IQuarkOAuthProvider;
use Quark\Extensions\OAuth\IQuarkOAuthConsumer;

use Quark\Extensions\OAuth\OAuthConfig;
use Quark\Extensions\OAuth\OAuthToken;

/**
 * Class SocialNetwork
 *
 * @package Quark\Extensions\SocialNetwork
 */
class SocialNetwork implements IQuarkOAuthConsumer {
	/**
	 * @var OAuthConfig $_config
	 */
	private $_config;

	/**
	 * @var OAuthToken $_token
	 */
	private $_token;

	/**
	 * @var IQuarkOAuthProvider|IQuarkSocialNetworkProvider $_provider
	 */
	private $_provider;

	/**
	 * @param string $config = ''
	 */
	public function __construct ($config = '') {
		if ($config == '') return;

		$this->_config = Quark::Config()->Extension($config);
		$this->_provider = $this->_config->Provider();
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
	 * @param IQuarkOAuthProvider $provider
	 *
	 * @return mixed
	 */
	public function OAuthProvider (IQuarkOAuthProvider $provider) {
		$this->_provider = $provider;
	}

	/**
	 * @param string $redirect
	 * @param string[] $scope
	 *
	 * @return string
	 */
	public function OAuthLoginURL ($redirect, $scope) {
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
	 * @param QuarkDTO $request
	 * @param string $redirect
	 *
	 * @return QuarkModel|OAuthToken
	 */
	public function OAuthTokenFromRequest (QuarkDTO $request, $redirect) {
		return $this->_provider->OAuthToken($request, $redirect);
	}

	/**
	 * @param string $url = ''
	 * @param QuarkDTO $request = null
	 * @param QuarkDTO $response = null
	 *
	 * @return mixed
	 */
	public function API ($url = '', QuarkDTO $request = null, QuarkDTO $response = null) {
		try {
			return $this->_provider->SocialNetworkAPI($url, $request, $response);
		}
		catch (SocialNetworkAPIException $e) {
			Quark::Log('[SocialNetworks.' . QuarkObject::ClassOf($this->_provider) . '] API error:');

			Quark::Trace($e->Request());
			Quark::Trace($e->Response());

			return null;
		}
	}

	/**
	 * @param string $user = ''
	 *
	 * @return SocialNetworkUser
	 */
	public function User ($user = '') {
		return $this->_provider->SocialNetworkUser($user);
	}

	/**
	 * @param string $user = ''
	 * @param int $count = 0
	 * @param int $offset = 0
	 *
	 * @return SocialNetworkUser[]
	 */
	public function Friends ($user = '', $count = 0, $offset = 0) {
		return $this->_provider->SocialNetworkFriends($user, $count, $offset);
	}
}