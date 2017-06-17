<?php
namespace Quark\Extensions\SocialNetwork;

/**
 * Interface IQuarkSocialNetworkProvider
 *
 * @package Quark\Extensions\SocialNetwork
 */
interface IQuarkSocialNetworkProvider {
	/**
	 * @param string $user
	 *
	 * @return SocialNetworkUser
	 */
	public function SocialNetworkUser($user);

	/**
	 * @param string $user
	 * @param int $count
	 * @param int $offset
	 *
	 * @return SocialNetworkUser[]
	 */
	public function SocialNetworkFriends($user, $count, $offset);
}