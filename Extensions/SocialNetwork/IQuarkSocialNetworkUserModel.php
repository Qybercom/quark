<?php
namespace Quark\Extensions\SocialNetwork;

use Quark\IQuarkModel;

/**
 * Interface IQuarkSocialNetworkUserModel
 *
 * @package Quark\Extensions\SocialNetwork
 */
interface IQuarkSocialNetworkUserModel extends IQuarkModel {
	/**
	 * @param SocialNetwork $network
	 *
	 * @return mixed
	 */
	public function SocialKey($network);
	
	/**
	 * @param SocialNetwork $network
	 * @param SocialNetworkUser $profile
	 *
	 * @return mixed
	 */
	public function SocialLogin(SocialNetwork $network, SocialNetworkUser $profile);
}