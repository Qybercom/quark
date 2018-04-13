<?php
namespace Quark\Extensions\OAuth;

use Quark\IQuarkLinkedModel;
use Quark\IQuarkModel;
use Quark\IQuarkStrongModel;

use Quark\Quark;
use Quark\QuarkArchException;
use Quark\QuarkDate;
use Quark\QuarkDTO;
use Quark\QuarkModel;
use Quark\QuarkModelBehavior;
use Quark\QuarkObject;

/**
 * Class OAuthToken
 *
 * @property string $config
 * @property string $access_token
 * @property string $refresh_token
 * @property QuarkDate $refreshed
 * @property bool $permanent
 * @property string $token_type = self::TYPE_BEARER
 * @property string $code
 * @property int $expires_in = 0
 * @property string $oauth_token_secret
 * @property string $api_user
 * @property string $verification_uri
 * @property string $user_code
 * @property string $device_code
 * @property int $interval
 *
 * @package Quark\Extensions\OAuth
 */
class OAuthToken implements IQuarkModel, IQuarkStrongModel, IQuarkLinkedModel {
	const TYPE_BEARER = 'Bearer';

	use QuarkModelBehavior;

	/**
	 * @param string $config
	 */
	public function __construct ($config = '') {
		if (func_num_args() != 0)
			$this->OAuthConfig($config);
	}

	/**
	 * @param string $config = ''
	 *
	 * @return OAuthConfig
	 *
	 * @throws QuarkArchException
	 */
	public function OAuthConfig ($config = '') {
		if (func_num_args() != 0)
			$this->config = $config;

		try {
			return Quark::Config()->Extension($this->config);
		}
		catch (QuarkArchException $e) {
			throw new QuarkArchException('[OAuthToken] There is no OAuthConfig registered with provided key "' . $this->config . '"');
		}
	}

	/**
	 * @return IQuarkOAuthConsumer
	 *
	 * @throws QuarkArchException
	 */
	public function Consumer () {
		return $this->OAuthConfig()->Consumer($this);
	}

	/**
	 * @return IQuarkOAuthProvider
	 *
	 * @throws QuarkArchException
	 */
	public function Provider () {
		$this->OAuthConfig()->Consumer($this);

		return $this->OAuthConfig()->Provider();
	}

	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'config' => '',
			'access_token' => '',
			'refresh_token' => '',
			'refreshed' => QuarkDate::GMTNow(),
			'permanent' => false,
			'token_type' => self::TYPE_BEARER,
			'code' => '',
			'expires_in' => 0,
			'oauth_token_secret' => '',
			'api_user' => '',
			'verification_uri' => '',
			'user_code' => '',
			'device_code' => '',
			'interval' => ''
		);
	}

	/**
	 * @return mixed
	 */
	public function Rules () {
		// TODO: Implement Rules() method.
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Link ($raw) {
		return self::FromMeta($raw);
	}

	/**
	 * @return mixed
	 */
	public function Unlink () {
		return self::MetaEncode($this->Extract());
	}

	/**
	 * @return bool
	 */
	public function Expired () {
		if ($this->permanent) return false;

		$now = QuarkDate::GMTNow();

		return $now->Later($this->refreshed->Offset('+' . $this->expires_in . ' seconds', true));
	}

	/**
	 * @param $access
	 * @param $refresh
	 * @param $expire
	 *
	 * @return QuarkModel|OAuthToken
	 */
	public function Refresh ($access, $refresh, $expire) {
		$this->access_token = $access;
		$this->refresh_token = $refresh;
		$this->expires_in = $expire;

		$this->refreshed = QuarkDate::GMTNow();

		return $this->Container();
	}

	/**
	 * @return object
	 */
	public function ExtractOAuth () {
		return $this->Extract(array(
			'access_token',
			'expires_in',
			'refresh_token',
			'token_type'
		));
	}

	/**
	 * @param QuarkDTO $request = null
	 * @param string $redirect = ''
	 *
	 * @return QuarkModel|OAuthToken
	 *
	 * @throws QuarkArchException
	 */
	public function InitFromRequest (QuarkDTO $request = null, $redirect = '') {
		if ($request == null) return null;

		try {
			$token = $this->Provider()->OAuthTokenFromRequest($request, $redirect);

			if ($token == null)
				throw new QuarkArchException('[OAuthToken::InitFromRequest.' . QuarkObject::ClassOf($this->Provider()) . '] Can not create OAuthToken from request: OAuth provider returned invalid OAuthToken object');

			$token->OAuthConfig($this->config);

			/**
			 * @var OAuthToken $model
			 */
			$model = $token->Model();
			$this->Provider()->OAuthConsumer($model);

			return $token;
		}
		catch (OAuthAPIException $e) {
			Quark::Log('[OAuthToken::InitFromRequest.' . QuarkObject::ClassOf($this->Provider()) . '] API error:');

			Quark::Trace($e->Request());
			Quark::Trace($e->Response());

			return null;
		}
	}

	/**
	 * @param object|array $params = []
	 *
	 * @return string
	 */
	public static function MetaEncode ($params = []) {
		return base64_encode(json_encode($params));
	}

	/**
	 * @param string $meta = ''
	 *
	 * @return object
	 */
	public static function MetaDecode ($meta = '') {
		return json_decode(base64_decode($meta));
	}

	/**
	 * @param string $config
	 * @param QuarkDTO $request = null
	 * @param string $redirect = ''
	 *
	 * @return QuarkModel|OAuthToken
	 */
	public static function FromRequest ($config, QuarkDTO $request = null, $redirect = '') {
		$token = new self($config);

		return $token->InitFromRequest($request, $redirect);
	}

	/**
	 * @param string $meta = ''
	 * @param string $config = ''
	 *
	 * @return QuarkModel|OAuthToken
	 */
	public static function FromMeta ($meta = '', $config = '') {
		return new QuarkModel(func_num_args() == 2 ? new OAuthToken($config) : new OAuthToken(), self::MetaDecode($meta));
	}

	/**
	 * @param array|object $params = []
	 *
	 * @return OAuthToken
	 */
	public static function Build ($params = []) {
		return self::FromMeta(self::MetaEncode($params))->Model();
	}
}