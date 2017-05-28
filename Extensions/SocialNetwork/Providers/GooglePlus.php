<?php
namespace Quark\Extensions\SocialNetwork\Providers;

use Quark\QuarkDTO;

use Quark\Extensions\OAuth\IQuarkOAuthConsumer;
use Quark\Extensions\OAuth\IQuarkOAuthProvider;
use Quark\Extensions\OAuth\OAuthToken;
use Quark\Extensions\OAuth\Providers\GoogleOAuth;

use Quark\Extensions\SocialNetwork\IQuarkSocialNetworkProvider;
use Quark\Extensions\SocialNetwork\SocialNetwork;
use Quark\Extensions\SocialNetwork\OAuthAPIException;
use Quark\Extensions\SocialNetwork\SocialNetworkUser;

/**
 * Class GooglePlus
 *
 * @package Quark\Extensions\SocialNetwork\Providers
 */
class GooglePlus extends GoogleOAuth implements IQuarkOAuthProvider, IQuarkSocialNetworkProvider {
	/**
	 * @param OAuthToken $token
	 *
	 * @return IQuarkOAuthConsumer
	 */
	public function OAuthConsumer (OAuthToken $token) {
		return new SocialNetwork();
	}

	/**
	 * @param string $url
	 * @param QuarkDTO $request
	 * @param QuarkDTO $response

	 *
*@return QuarkDTO|null
	 * @throws OAuthAPIException
	 */
	public function SocialNetworkAPI ($url, QuarkDTO $request, QuarkDTO $response) {
		// TODO: Implement SocialNetworkAPI() method.
	}

	/**
	 * @param string $user
	 *
	 * @return SocialNetworkUser
	 */
	public function SocialNetworkUser ($user) {
		// TODO: Implement SocialNetworkUser() method.
	}

	/**
	 * @param string $user
	 * @param int $count
	 * @param int $offset
	 *
	 * @return SocialNetworkUser[]
	 */
	public function SocialNetworkFriends ($user, $count, $offset) {
		// TODO: Implement SocialNetworkFriends() method.
	}
}