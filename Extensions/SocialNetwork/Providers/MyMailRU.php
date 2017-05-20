<?php
namespace Quark\Extensions\SocialNetwork\Providers;

use Quark\Quark;
use Quark\QuarkURI;
use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkModel;

use Quark\Extensions\OAuth\IQuarkOAuthConsumer;
use Quark\Extensions\OAuth\IQuarkOAuthProvider;
use Quark\Extensions\OAuth\OAuthToken;

use Quark\Extensions\SocialNetwork\IQuarkSocialNetworkProvider;
use Quark\Extensions\SocialNetwork\SocialNetwork;
use Quark\Extensions\SocialNetwork\SocialNetworkAPIException;
use Quark\Extensions\SocialNetwork\SocialNetworkUser;

/**
 * Class MyMailRU
 *
 * @package Quark\Extensions\SocialNetwork\Providers
 */
class MyMailRU implements IQuarkOAuthProvider, IQuarkSocialNetworkProvider {
	const URL_OAUTH = 'https://connect.mail.ru';
	const URL_API = 'http://www.appsmail.ru/platform/api';

	/**
	 * @var string $_appId
	 */
	private $_appId = '';

	/**
	 * @var string $_appSecret = ''
	 */
	private $_appSecret = '';

	/**
	 * @param OAuthToken $token
	 *
	 * @return IQuarkOAuthConsumer
	 */
	public function OAuthConsumer (OAuthToken $token) {
		return new SocialNetwork();
	}

	/**
	 * @param string $appId
	 * @param string $appSecret
	 *
	 * @return mixed
	 */
	public function OAuthApplication ($appId, $appSecret) {
		$this->_appId = $appId;
		$this->_appSecret = $appSecret;
	}

	/**
	 * @param string $redirect
	 * @param string[] $scope
	 *
	 * @return string
	 */
	public function OAuthLoginURL ($redirect, $scope) {
		return QuarkURI::Build(self::URL_OAUTH . '/oauth/authorize?', array(
			'client_id=' => $this->_appId,
   			'redirect_uri' => $redirect,
   			'response_type' => 'code',
		));
	}

	/**
	 * @param string $redirect
	 *
	 * @return string
	 */
	public function OAuthLogoutURL ($redirect) {
		// TODO: Implement OAuthLogoutURL() method.
	}

	/**
	 * @param QuarkDTO $request
	 * @param string $redirect
	 *
	 * @return QuarkModel|OAuthToken
	 */
	public function OAuthToken (QuarkDTO $request, $redirect) {
		// TODO: Implement OAuthToken() method.
	}

	/**
	 * @param string $url
	 * @param QuarkDTO $request
	 * @param QuarkDTO $response
	 *
	 * @return QuarkDTO|null
	 * @throws SocialNetworkAPIException
	 */
	public function SocialNetworkAPI ($url, QuarkDTO $request, QuarkDTO $response) {
		// TODO: Implement SocialNetworkAPI() method.
	}

	/**
	 * @param string $user
	 *
	 * @return SocialNetworkUser
	 */
	public function SocialNetworkUser ($user) {
		// TODO: Implement SocialNetworkUser() method.
	}

	/**
	 * @param string $user
	 * @param int $count
	 * @param int $offset
	 *
	 * @return SocialNetworkUser[]
	 */
	public function SocialNetworkFriends ($user, $count, $offset) {
		// TODO: Implement SocialNetworkFriends() method.
	}

	/**
	 * @var string $_session = ''
	 */
	private $_session = '';

	/**
	 * @var string $_vid = ''
	 */
	private $_vid = '';

	/**
	 * @param QuarkDTO $request
	 * @param string $to
	 *
	 * @return string
	 */
	public function SessionFromRedirect (QuarkDTO $request, $to) {
		$response = $this->API('GET', '/oauth/token', array(
			'client_id' => $this->_appId,
			'client_secret' => $this->_appSecret,
			'redirect_uri' => $to,
			'code' => $request->code,
			'grant_type' => 'authorization_code'
		), self::URL_OAUTH);

		if ($response == null) return '';

		$this->_vid = $response->x_mailru_vid;

		return $this->_session = $response->access_token;
	}

	/**
	 * @param string $token
	 *
	 * @return string
	 */
	public function SessionFromToken ($token) {
		return $this->_session = $token;
	}

	/**
	 * @return string
	 */
	public function CurrentUser () {
		return $this->_vid;
	}

	/**
	 * @param string $method
	 * @param string $url
	 * @param array  $data
	 * @param string $base = self::URL_API
	 *
	 * @return QuarkDTO|\stdClass
	 */
	public function API ($method = '', $url = '', $data = [], $base = self::URL_API) {
		$request = new QuarkDTO(new QuarkFormIOProcessor());
		$request->Method($method);

		$get = $method == 'GET';

		if (!$get)
			$request->Data($data);

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		$out = QuarkHTTPClient::To($base . $url . '?' . http_build_query(array_merge_recursive($get ? $data : array()) + array(
			'access_token' => $this->_session
		)), $request, $response);

		if (isset($out->error)) {
			Quark::Log('MyMailRU.Exception: '
				. (isset($out->error->error_code) ? $out->error->error_code : '')
				. ': '
				. (isset($out->error->error_msg) ? $out->error->error_msg : ''),
				Quark::LOG_WARN);

			Quark::Trace($out);

			return null;
		}

		return $out;
	}
}