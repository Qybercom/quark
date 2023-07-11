<?php
namespace Quark\Extensions\SocialNetwork;

use Quark\Quark;

use Quark\Extensions\OAuth\OAuthUser;
use Quark\Extensions\OAuth\OAuthProviderBehavior;

/**
 * Trait SocialNetworkProviderBehavior
 *
 * @package Quark\Extensions\SocialNetwork
 */
trait SocialNetworkProviderBehavior {
	use OAuthProviderBehavior;

	/**
	 * @return OAuthUser
	 */
	public function OAuthUser () {
		if (!($this instanceof IQuarkSocialNetworkProvider)) {
			Quark::Log('[SocialNetworkProvider] Behavior must be used only in IQuarkSocialNetworkProvider implementations');

			return null;
		}

		$user = $this->SocialNetworkUser($this->SocialNetworkParameterUser(SocialNetwork::CURRENT_USER));

		return $user == null ? null : $user->OAuthUser();
	}
}