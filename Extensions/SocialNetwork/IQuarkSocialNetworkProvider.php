<?php
namespace Quark\Extensions\SocialNetwork;

/**
 * Interface IQuarkSocialNetworkProvider
 *
 * @package Quark\Extensions\SocialNetwork
 */
interface IQuarkSocialNetworkProvider {
	/**
	 * @param string $appId
	 * @param string $appSecret
	 *
	 * @return mixed
	 */
	public function Init($appId, $appSecret);

	/**
	 * @param string $to
	 * @param array $permissions
	 *
	 * @return string
	 */
	public function LoginURL($to, $permissions = []);

	/**
	 * @param string $to
	 *
	 * @return string
	 */
	public function LogoutURL($to);

	/**
	 * @param string $to
	 *
	 * @return mixed
	 */
	public function SessionFromRedirect($to);

	/**
	 * @param string $token
	 *
	 * @return mixed
	 */
	public function SessionFromToken($token);

	/**
	 * @param $user
	 *
	 * @return mixed
	 */
	public function Profile($user);

	/**
	 * @return mixed
	 */
	public function API();
}