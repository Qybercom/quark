<?php
namespace Quark\Extensions\SocialNetwork;

use Quark\IQuarkModel;
use Quark\IQuarkLinkedModel;
use Quark\IQuarkExtensionConfig;
use Quark\IQuarkModelWithDataProvider;

use Quark\Quark;
use Quark\QuarkField;
use Quark\QuarkModel;
use Quark\QuarkModelBehavior;

/**
 * Class SocialNetwork
 *
 * @package Quark\Extensions\SocialNetwork
 */
class SocialNetwork implements IQuarkModel, IQuarkLinkedModel, IQuarkModelWithDataProvider {
	use QuarkModelBehavior;

	/**
	 * @var string $social
	 */
	public $social = '';

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
	 * @var bool $_newUser = false
	 */
	private $_newUser = false;

	/**
	 * @param string $config
	 * @param string $token = ''
	 * @param string $id = ''
	 */
	public function __construct ($config, $token = '', $id = '') {
		$this->_config = Quark::Config()->Extension($config);
		$this->accessToken = (string)$token;
		$this->id = (string)$id;
		$this->social = $this->Name();
	}

	/**
	 * @return string
	 */
	public function __toString () {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function DataProvider () {
		return $this->_config->DataProvider();
	}

	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'social' => $this->Name(),
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
		if ($raw == null) return null;

		$social = json_decode(base64_decode($raw));

		if (!$social) return null;

		return QuarkModel::FindOne(new SocialNetwork($this->_config->Name()), array(
			'social' => (string)$social->social,
			'id' => (string)$social->id
		));
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
		return base64_encode(json_encode(array(
			'social' => (string)$this->Name(),
			'id' => (string)$this->id
		)));
	}

	/**
	 * @return QuarkModel|SocialNetwork
	 */
	public function StoredProfile () {
		return QuarkModel::FindOne($this, array(
			'social' => (string)$this->Name(),
			'id' => (string)$this->id
		));
	}

	/**
	 * @param IQuarkModel $model
	 * @param string $key
	 *
	 * @return QuarkModel|IQuarkModel
	 */
	public function User (IQuarkModel $model, $key) {
		$profile = $this->StoredProfile();

		if ($profile == null && $this->id != '' && $this->accessToken != '') {
			$profile = new QuarkModel($this);
			$profile->Create();
		}

		if ($profile == null) return null;

		$id = $this->Identifier();

		$user = QuarkModel::FindOne($model, array(
			$key => $id
		));

		if ($user == null) {
			$user = new QuarkModel($model, QuarkModel::StructureFromKey($key, $profile));
			$this->_newUser = true;
		}

		return $user;
	}

	/**
	 * @return bool
	 */
	public function IsNewUser () {
		return $this->_newUser;
	}

	/**
	 * @return bool
	 */
	public function IsConnected () {
		return $this->id != '' && $this->accessToken != '';
	}

	/**
	 * @return string
	 */
	public function Name () {
		return $this->_config->SocialNetwork()->Name();
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