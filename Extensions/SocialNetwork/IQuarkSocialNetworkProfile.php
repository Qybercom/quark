<?php
namespace Quark\Extensions\SocialNetwork;

/**
 * Interface IQuarkSocialNetworkProfile
 *
 * @package Quark\Extensions\SocialNetwork
 */
interface IQuarkSocialNetworkProfile {
	/**
	 * @param SocialNetworkUser $user
	 *
	 * @return mixed
	 */
	public function SocialNetworkProfile(SocialNetworkUser $user);
}