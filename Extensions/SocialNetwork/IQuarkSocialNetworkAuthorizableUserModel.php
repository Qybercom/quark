<?php
namespace Quark\Extensions\SocialNetwork;

use Quark\IQuarkAuthorizableModel;

/**
 * Interface IQuarkSocialNetworkAuthorizableUserModel
 *
 * @package Quark\Extensions\SocialNetwork
 */
interface IQuarkSocialNetworkAuthorizableUserModel extends IQuarkAuthorizableModel, IQuarkSocialNetworkUserModel {
	/**
	 * @param SocialNetwork $network
	 * @param SocialNetworkUser $profile
	 *
	 * @return int|bool
	 */
	public function SocialLoginLifetime(SocialNetwork $network, SocialNetworkUser $profile);
}