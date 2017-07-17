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
 * @property int $expires_in
 * @property string $oauth_token_secret
 *
 * @package Quark\Extensions\OAuth
 */
class OAuthToken implements IQuarkModel, IQuarkStrongModel, IQuarkLinkedModel {
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
			'expires_in' => 0,
			'oauth_token_secret' => ''
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
		return new QuarkModel(new OAuthToken(), self::MetaDecode($raw));
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
		$now = QuarkDate::GMTNow();

		return $now->Later($this->refreshed->Offset('+' . $this->expires_in . ' seconds'));
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
	 * @param QuarkDTO $request = null
	 * @param string $redirect = ''
	 *
	 * @return QuarkModel|OAuthToken
	 *
	 * @throws QuarkArchException
	 */
	public function FromRequest (QuarkDTO $request = null, $redirect = '') {
		if ($request == null) return null;

		try {
			$token = $this->Provider()->OAuthTokenFromRequest($request, $redirect);

			if ($token == null)
				throw new QuarkArchException('[OAuthToken::FromRequest.' . QuarkObject::ClassOf($this->Provider()) . '] Can not create OAuthToken from request: OAuth provider returned invalid OAuthToken object');

			$token->OAuthConfig($this->config);

			/**
			 * @var OAuthToken $model
			 */
			$model = $token->Model();
			$this->Provider()->OAuthConsumer($model);

			return $token;
		}
		catch (OAuthAPIException $e) {
			Quark::Log('[OAuthToken::FromRequest.' . QuarkObject::ClassOf($this->Provider()) . '] API error:');

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
	public static function InitFromRequest ($config, QuarkDTO $request = null, $redirect = '') {
		$token = new self($config);

		return $token->FromRequest($request, $redirect);
	}

	/**
	 * @param string $config
	 * @param string $access_token = ''
	 * @param string $refresh_token = ''
	 * @param string $oauth_token_secret = ''
	 * @param int $expires_in = 0
	 * @param QuarkDate $refreshed = null
	 *
	 * @return QuarkModel|OAuthToken
	 */
	public static function InitFromParams ($config, $access_token = '', $refresh_token = '', $oauth_token_secret = '', $expires_in = 0, QuarkDate $refreshed = null) {
		return new QuarkModel(new OAuthToken($config), array(
			'access_token' => $access_token,
			'refresh_token' => $refresh_token,
			'refreshed' => $refreshed ? $refreshed : QuarkDate::GMTNow(),
			'expires_in' => $expires_in,
			'oauth_token_secret' => $oauth_token_secret
		));
	}

	/**
	 * @param string $config
	 * @param string $meta = ''
	 *
	 * @return QuarkModel|OAuthToken
	 */
	public static function InitFromMeta ($config, $meta = '') {
		return new QuarkModel(new OAuthToken($config), self::MetaDecode($meta));
	}
}