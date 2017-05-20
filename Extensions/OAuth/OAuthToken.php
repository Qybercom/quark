<?php
namespace Quark\Extensions\OAuth;

use Quark\IQuarkLinkedModel;
use Quark\IQuarkModel;
use Quark\IQuarkStrongModel;

use Quark\Quark;
use Quark\QuarkArchException;
use Quark\QuarkDate;
use Quark\QuarkModel;
use Quark\QuarkModelBehavior;

/**
 * Class OAuthToken
 *
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
	 * @var string $_config = ''
	 */
	private $_config = '';

	/**
	 * OAuthToken constructor.
	 *
	 * @param string $config
	 */
	public function __construct ($config = '') {
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
			$this->_config = $config;

		$out = Quark::Config()->Extension($this->_config);

		if (!($out instanceof OAuthConfig))
			throw new QuarkArchException('[OAuthToken] There is no OAuthConfig registered with provided key ' . $this->_config);

		return $out;
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
	 * @return mixed
	 */
	public function Fields () {
		return array(
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
		return new QuarkModel(new OAuthToken(), json_decode(base64_decode($raw)));
	}

	/**
	 * @return mixed
	 */
	public function Unlink () {
		return base64_encode(json_encode($this->Extract()));
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
}