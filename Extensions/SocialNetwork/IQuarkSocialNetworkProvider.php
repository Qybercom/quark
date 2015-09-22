<?php
namespace Quark\Extensions\SocialNetwork;

/**
 * Interface IQuarkSocialNetworkProvider
 *
 * @package Quark\Extensions\SocialNetwork
 */
interface IQuarkSocialNetworkProvider {
	/**
	 * @return string
	 */
	public function Name();

	/**
	 * @param string $appId
	 * @param string $appSecret
	 *
	 * @return mixed
	 */
	public function Init($appId, $appSecret);

	/**
	 * @param string $to
	 * @param string[] $permissions
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
	 * @param string $code
	 *
	 * @return string
	 */
	public function SessionFromRedirect($to, $code);

	/**
	 * @param string $token
	 *
	 * @return string
	 */
	public function SessionFromToken($token);

	/**
	 * @param $user
	 *
	 * @return SocialNetworkUser
	 */
	public function Profile($user);

	/**
	 * @return \Quark\QuarkDTO
	 */
	public function API();

	/**
	 * @return string
	 */
	public function CurrentUser();
}