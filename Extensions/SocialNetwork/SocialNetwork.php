<?php
namespace Quark\Extensions\SocialNetwork;

use Quark\IQuarkModel;
use Quark\IQuarkLinkedModel;
use Quark\IQuarkExtensionConfig;
use Quark\IQuarkModelWithAfterFind;
use Quark\IQuarkModelWithDataProvider;

use Quark\Quark;
use Quark\QuarkField;
use Quark\QuarkModel;
use Quark\QuarkModelBehavior;

/**
 * Class SocialNetwork
 *
 * @property string $userId
 * @property string $accessToken
 * @property string $social
 *
 * @package Quark\Extensions\SocialNetwork
 */
class SocialNetwork implements IQuarkModel, IQuarkLinkedModel, IQuarkModelWithDataProvider, IQuarkModelWithAfterFind {
	use QuarkModelBehavior;

	/**
	 * @var string $_config
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
		$this->_config = $config;
		$this->accessToken = (string)$token;
		$this->userId = (string)$id;
	}

	/**
	 * @return string
	 */
	public function __toString () {
		return $this->userId;
	}

	/**
	 * @return IQuarkExtensionConfig|SocialNetworkConfig
	 */
	private function _config () {
		return Quark::Config()->Extension($this->_config);
	}

	/**
	 * @return string
	 */
	public function DataProvider () {
		return $this->_config()->DataProvider();
	}

	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'social' => $this->Name(),
			'userId' => '',
			'accessToken' => '',
		);
	}

	/**
	 * @return mixed
	 */
	public function Rules () {
		$this->userId = (string)$this->userId;
		$this->accessToken = (string)$this->accessToken;

		return array(
			QuarkField::Type($this->userId, QuarkField::TYPE_STRING),
			QuarkField::Type($this->accessToken, QuarkField::TYPE_STRING)
		);
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Link ($raw) {
		return $raw == null ? null : QuarkModel::FindOneById(new SocialNetwork($this->_config), $raw);
	}

	/**
	 * @return mixed
	 */
	public function Unlink () {
		return $this->userId != '' && $this->accessToken != '' ? $this->Pk() : null;
	}

	/**
	 * @param $raw
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function AfterFind ($raw, $options) {
		$this->SessionFromToken($this->accessToken);
	}

	/**
	 * @param IQuarkModel $model
	 * @param string $key
	 *
	 * @return QuarkModel|IQuarkModel
	 */
	public function User (IQuarkModel $model, $key) {
		/**
		 * @var QuarkModel|SocialNetwork $profile
		 */
		$profile = QuarkModel::FindOne($this, array(
			'social' => (string)$this->Name(),
			'userId' => (string)$this->userId
		));

		if ($profile == null && $this->userId != '' && $this->accessToken != '') {
			$profile = new QuarkModel($this);
			$profile->Create();
		}

		if ($profile == null) return null;

		$profile->accessToken = (string)$this->accessToken;
		$profile->Save();

		$user = QuarkModel::FindOne($model, array(
			$key => $profile->Pk()
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
		return $this->userId != '' && $this->accessToken != '';
	}

	/**
	 * @return string
	 */
	public function Name () {
		return $this->_config()->SocialNetwork()->Name();
	}

	/**
	 * @param string $to
	 * @param string[] $permissions
	 *
	 * @return string
	 */
	public function LoginURL ($to = '', $permissions = []) {
		return $this->_config()->SocialNetwork()->LoginURL($to, $permissions);
	}

	/**
	 * @param string $to
	 *
	 * @return string
	 */
	public function LogoutURL ($to = '') {
		return $this->_config()->SocialNetwork()->LogoutURL($to);
	}

	/**
	 * @param string $method
	 * @param string $description
	 *
	 * @return null
	 */
	private function _log ($method, $description) {
		Quark::Log('SocialNetwork.' . $method . ' for ' . get_class($this->_config()->SocialNetwork()) . ' failed. ' . $description, Quark::LOG_WARN);
		return null;
	}

	/**
	 * @param string $method
	 * @param array $args
	 *
	 * @return mixed
	 */
	private function _call ($method, $args = []) {
		return call_user_func_array(array($this->_config()->SocialNetwork(), $method), $args);
	}

	/**
	 * @param string $method
	 *
	 * @return SocialNetwork
	 */
	private function _session ($method) {
		if (func_num_args() < 2)
			return $this->_log($method, 'Not enough data for session start.');

		$token = $this->_call($method, array_slice(func_get_args(), 1));

		if ($token == null)
			return $this->_log($method, 'Invalid token ' . $token);

		$this->accessToken = $token;
		$this->social = $this->Name();

		return $this;
	}

	/**
	 * @param string $to
	 * @param string $code
	 *
	 * @return SocialNetwork
	 */
	public function SessionFromRedirect ($to, $code) {
		return $this->_session('SessionFromRedirect', $to, $code);
	}

	/**
	 * @param $token
	 *
	 * @return SocialNetwork
	 */
	public function SessionFromToken ($token) {
		return $this->_session('SessionFromToken', $token);
	}

	/**
	 * @return SocialNetworkUser
	 */
	public function Init () {
		$profile = $this->Profile($this->_config()->SocialNetwork()->CurrentUser());

		if (!$profile) return null;

		$this->userId = $profile->ID();

		return $profile;
	}

	/**
	 * @param string $user
	 *
	 * @return string
	 */
	private function _user ($user) {
		return $user === null ? $this->_config()->SocialNetwork()->CurrentUser() : $user;
	}

	/**
	 * @return mixed
	 */
	public function API () {
		return $this->_call('API', func_get_args());
	}

	/**
	 * @param string $user
	 * @param string[] $fields
	 *
	 * @return SocialNetworkUser
	 */
	public function Profile ($user = null, $fields = null) {
		return $this->_config()->SocialNetwork()->Profile($this->_user($user), $fields);
	}

	/**
	 * @param string $user
	 * @param string[] $fields
	 * @param int $count = 10
	 * @param int $offset = 0
	 *
	 * @return SocialNetworkUser[]
	 */
	public function Friends ($user = null, $fields = null, $count = 10, $offset = 0) {
		return $this->_config()->SocialNetwork()->Friends($this->_user($user), $fields, $count, $offset);
	}
}