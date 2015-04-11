<?php
namespace Quark\Extensions\Facebook;

use Quark\IQuarkModel;
use Quark\IQuarkStrongModel;
use Quark\IQuarkLinkedModel;

use Quark\QuarkModel;

use Facebook\Entities\AccessToken;

/**
 * Class FacebookAccessToken
 *
 * @package Quark\Extensions\Facebook
 */
class FacebookAccessToken implements IQuarkModel, IQuarkStrongModel, IQuarkLinkedModel {
	/**
	 * @param AccessToken $token
	 */
	public function __construct (AccessToken $token = null) {
		if ($token == null) return;

		$this->accessToken = (string)$token;
		$this->machineId = $token->getMachineId();
		$this->expiresAt = $token->getExpiresAt();
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
			'machineId' => '',
			'expiresAt' => ''
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
	 * @param string $config
	 *
	 * @return Facebook
	 */
	public function Session ($config) {
		return Facebook::SessionFromToken($config, $this->accessToken);
	}
}