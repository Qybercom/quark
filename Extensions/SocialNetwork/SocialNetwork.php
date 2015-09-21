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
	 * @var string $id
	 */
	public $id = '';

	/**
	 * @var string $accessToken
	 */
	public $accessToken = '';

	/**
	 * @var IQuarkExtensionConfig|SocialNetworkConfig $_config
	 */
	private $_config;

	/**
	 * @param string $config
	 * @param string $token = ''
	 * @param string $id = ''
	 */
	public function __construct ($config, $token = '', $id = '') {
		$this->_config = Quark::Config()->Extension($config);
		$this->accessToken = (string)$token;
		$this->id = (string)$id;
	}

	/**
	 * @return string
	 */
	public function __toString () {
		return $this->id;
	}

	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'id' => '',
			'accessToken' => '',
		);
	}

	/**
	 * @return mixed
	 */
	public function Rules () {
		$this->id = (string)$this->id;
		$this->accessToken = (string)$this->accessToken;

		return array(
			QuarkField::Type($this->id, QuarkField::TYPE_STRING),
			QuarkField::Type($this->accessToken, QuarkField::TYPE_STRING)
		);
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Link ($raw) {
		$social = json_decode($raw);

		if (!$social) return null;

		if ($social)
			$this->_session('Redirect', $this->_config->SocialNetwork()->SessionFromToken($social->accessToken));

		return new QuarkModel($this, $social);
	}

	/**
	 * @return mixed
	 */
	public function Unlink () {
		return $this->id != '' && $this->accessToken != '' ? $this->Identifier() : null;
	}

	/**
	 * @return string
	 */
	public function Identifier () {
		return json_encode($this);
	}

	/**
	 * @param string $to
	 * @param string[] $permissions
	 *
	 * @return string
	 */
	public function LoginURL ($to = '', $permissions = []) {
		return $this->_config->SocialNetwork()->LoginURL($to, $permissions);
	}

	/**
	 * @param string $to
	 *
	 * @return string
	 */
	public function LogoutURL ($to = '') {
		return $this->_config->SocialNetwork()->LogoutURL($to);
	}

	/**
	 * @param string $method
	 * @param string $token
	 *
	 * @return SocialNetwork
	 */
	private function _session ($method, $token) {
		if ($token == null) {
			Quark::Log('SocialNetwork.SessionFrom' . $method . ' for ' . get_class($this->_config->SocialNetwork()) . ' failed. Invalid token.', Quark::LOG_WARN);
			return null;
		}

		$profile = $this->Profile($this->_config->SocialNetwork()->CurrentUser());

		if ($profile == null) {
			Quark::Log('SocialNetwork.SessionFrom' . $method . ' for ' . get_class($this->_config->SocialNetwork()) . ' failed. Profile error.', Quark::LOG_WARN);
			return null;
		}

		$this->accessToken = $token;
		$this->id = $profile->ID();

		return $this;
	}

	/**
	 * @param string $to
	 * @param string $code
	 *
	 * @return SocialNetwork
	 */
	public function SessionFromRedirect ($to, $code) {
		return $this->_session('Redirect', $this->_config->SocialNetwork()->SessionFromRedirect($to, $code));
	}

	/**
	 * @param $token
	 *
	 * @return SocialNetwork
	 */
	public function SessionFromToken ($token) {
		return $this->_session('Token', $this->_config->SocialNetwork()->SessionFromToken($token));
	}

	/**
	 * @return mixed
	 */
	public function API () {
		return call_user_func_array(array($this->_config->SocialNetwork(), 'API'), func_get_args());
	}

	/**
	 * @param string $user
	 *
	 * @return SocialNetworkUser
	 */
	public function Profile ($user = '') {
		return $this->_config->SocialNetwork()->Profile(func_num_args() != 0 ? $user : $this->id);
	}
}