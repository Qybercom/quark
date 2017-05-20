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
	 * @param string $url
	 * @param QuarkDTO $request
	 * @param QuarkDTO $response
	 *
	 * @return QuarkDTO|null
	 *
	 * @throws SocialNetworkAPIException
	 */
	public function SocialNetworkAPI($url, QuarkDTO $request, QuarkDTO $response);

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