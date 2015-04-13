<?php
namespace Quark\Extensions\SocialNetwork;

use Quark\IQuarkModel;
use Quark\IQuarkLinkedModel;
use Quark\IQuarkExtensionConfig;

use Quark\Quark;
use Quark\QuarkField;
use Quark\QuarkModel;
use Quark\QuarkModelBehavior;

/**
 * Class SocialNetwork
 *
 * @package Quark\Extensions\SocialNetwork
 */
class SocialNetwork implements IQuarkModel, IQuarkLinkedModel {
	use QuarkModelBehavior;

	/**
	 * @var IQuarkExtensionConfig|SocialNetworkConfig $_config
	 */
	private $_config;

	/**
	 * @param string $config
	 * @param string $token
	 */
	public function __construct ($config, $token = '') {
		$this->_config = Quark::Config()->Extension($config);
		$this->accessToken = (string)$token;
	}

	/**
	 * @return string
	 */
	public function __toString () {
		return $this->accessToken;
	}

	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'accessToken' => '',
		);
	}

	/**
	 * @return mixed
	 */
	public function Rules () {
		$this->accessToken = (string)$this->accessToken;

		return array(
			QuarkField::Type($this->accessToken, QuarkField::TYPE_STRING)
		);
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Link ($raw) {
		if ($this->SessionFromToken($raw) == null) return null;

		return new QuarkModel($this, array(
			'accessToken' => $raw
		));
	}

	/**
	 * @return mixed
	 */
	public function Unlink () {
		return $this->accessToken;
	}

	/**
	 * @param string $to
	 * @param array  $permissions
	 *
	 * @return string
	 */
	public function LoginURL ($to, $permissions = []) {
		return $this->_config->SocialNetwork()->LoginURL($to, $permissions);
	}

	/**
	 * @param string $to
	 *
	 * @return string
	 */
	public function LogoutURL ($to) {
		return $this->_config->SocialNetwork()->LogoutURL($to);
	}

	/**
	 * @param string $to
	 *
	 * @return SocialNetwork
	 */
	public function SessionFromRedirect ($to) {
		$this->accessToken = $this->_config->SocialNetwork()->SessionFromRedirect($to);

		if ($this->accessToken != null) return $this;

		Quark::Log('SocialNetwork.SessionFromRedirect for ' . get_class($this->_config->SocialNetwork()) . ' failed', Quark::LOG_WARN);
		return null;
	}

	/**
	 * @param $token
	 *
	 * @return SocialNetwork
	 */
	public function SessionFromToken ($token) {
		$this->accessToken = $this->_config->SocialNetwork()->SessionFromToken($token);

		if ($this->accessToken != null) return $this;

		Quark::Log('SocialNetwork.SessionFromToken for ' . get_class($this->_config->SocialNetwork()) . ' failed', Quark::LOG_WARN);
		return null;
	}

	/**
	 * @param $user
	 *
	 * @return mixed
	 */
	public function Profile ($user) {
		return $this->PopulateWith($this->_config->SocialNetwork()->Profile($user));
	}
}