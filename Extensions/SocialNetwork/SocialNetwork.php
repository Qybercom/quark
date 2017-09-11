<?php
namespace Quark\Extensions\SocialNetwork;

use Quark\Quark;
use Quark\QuarkObject;
use Quark\QuarkModel;

use Quark\Extensions\OAuth\IQuarkOAuthProvider;
use Quark\Extensions\OAuth\IQuarkOAuthConsumer;

use Quark\Extensions\OAuth\OAuthConsumerBehavior;
use Quark\Extensions\OAuth\OAuthAPIException;

/**
 * Class SocialNetwork
 *
 * @package Quark\Extensions\SocialNetwork
 */
class SocialNetwork implements IQuarkOAuthConsumer {
	use OAuthConsumerBehavior;

	/**
	 * @param string $config = ''
	 */
	public function __construct ($config = '') {
		if (func_num_args() != 0)
			$this->OAuthConfig($config);
	}

	/**
	 * @return IQuarkOAuthProvider|IQuarkSocialNetworkProvider
	 */
	private function &_provider () {
		return $this->_provider;
	}

	/**
	 * @param OAuthAPIException $e
	 * @param string $action = ''
	 * @param string $message = ''
	 * @param $out = null
	 *
	 * @return mixed
	 */
	private function _error (OAuthAPIException $e, $action = '', $message = '', $out = null) {
		Quark::Log('[SocialNetwork::' . $action . ' ' . QuarkObject::ClassOf($this->_provider) . '] ' . $message . '. API error:', Quark::LOG_WARN);

		Quark::Trace($e->Request());
		Quark::Trace($e->Response());

		return $out;
	}

	/**
	 * @param QuarkModel|IQuarkSocialNetworkProfile $model
	 *
	 * @return QuarkModel|IQuarkSocialNetworkProfile
	 */
	public function Profile (QuarkModel $model = null) {
		if ($model == null) return null;
		if (!($model->Model() instanceof IQuarkSocialNetworkProfile)) return null;

		$out = $model->SocialNetworkProfile($this->User());

		return $out || $out === null ? $model : null;
	}

	/**
	 * @param array|object $data = []
	 *
	 * @return SocialNetworkUser
	 */
	public function ProfileFrom ($data = []) {
		return $this->_provider()->SocialNetworkProfile($data);
	}

	/**
	 * @param string $user = ''
	 *
	 * @return SocialNetworkUser
	 */
	public function User ($user = '') {
		try {
			return $this->_provider()->SocialNetworkUser($user);
		}
		catch (OAuthAPIException $e) {
			return $this->_error($e, 'User', 'Can not get user', null);
		}
	}

	/**
	 * @param string $user = ''
	 * @param int $count = 0
	 * @param int $offset = 0
	 *
	 * @return SocialNetworkUser[]
	 */
	public function Friends ($user = '', $count = 0, $offset = 0) {
		try {
			return $this->_provider()->SocialNetworkFriends($user, $count, $offset);
		}
		catch (OAuthAPIException $e) {
			return $this->_error($e, 'Friends', 'Can not get friends', array());
		}
	}

	/**
	 * @param SocialNetworkPost $post
	 *
	 * @return SocialNetworkPost
	 */
	public function Publish (SocialNetworkPost $post) {
		try {
			return $this->_provider()->SocialNetworkPublish($post);
		}
		catch (OAuthAPIException $e) {
			return $this->_error($e, 'Publish', 'Can not publish article', null);
		}
	}
}