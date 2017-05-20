<?php
namespace Quark\Extensions\SocialNetwork;

use Quark\QuarkDTO;

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
	public function SocialNetworkApplication($appId, $appSecret);

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
	 * @param QuarkDTO $request
	 * @param string $to
	 *
	 * @return string
	 */
	public function SessionFromRedirect(QuarkDTO $request, $to);

	/**
	 * @param string $token
	 *
	 * @return string
	 */
	public function SessionFromToken($token);

	/**
	 * @return string
	 */
	public function CurrentUser();

	/**
	 * @return \Quark\QuarkDTO
	 */
	public function API();

	/**
	 * @param string $user
	 * @param string[] $fields
	 *
	 * @return SocialNetworkUser
	 */
	public function Profile($user, $fields);

	/**
	 * @param string $user
	 * @param string[] $fields
	 * @param int $count
	 * @param int $offset
	 *
	 * @return SocialNetworkUser[]
	 */
	public function Friends($user, $fields, $count, $offset);
}