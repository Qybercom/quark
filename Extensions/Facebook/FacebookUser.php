<?php
namespace Quark\Extensions\Facebook;

use Quark\IQuarkAuthorizationProvider;
use Quark\IQuarkModel;
use Quark\IQuarkLinkedModel;
use Quark\IQuarkAuthorizableModel;

/**
 * Class FacebookUser
 *
 * @package Quark\Extensions\Facebook
 */
class FacebookUser implements IQuarkModel, IQuarkLinkedModel, IQuarkAuthorizableModel {
	/**
	 * @param $criteria
	 *
	 * @return mixed
	 */
	public function Authorize ($criteria) {
		// TODO: Implement Authorize() method.
	}

	/**
	 * @param IQuarkAuthorizationProvider|Facebook $provider
	 * @param $request
	 *
	 * @return mixed
	 */
	public function RenewSession (IQuarkAuthorizationProvider $provider, $request) {
		print_r($provider);
		print_r($provider->API('GET', '/me'));
	}

	/**
	 * @return mixed
	 */
	public function Fields () {
		// TODO: Implement Fields() method.
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
		// TODO: Implement Link() method.
	}

	/**
	 * @return mixed
	 */
	public function Unlink () {
		// TODO: Implement Unlink() method.
	}
}