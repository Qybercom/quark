<?php
namespace Quark\Extensions\AppleSignIn;

use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;

use Quark\Extensions\JOSE\IJOSEJWTIdentity;
use Quark\Extensions\JOSE\IJOSEJWTIdentityProvider;
use Quark\Extensions\JOSE\JWK\IJOSEJWKProvider;
use Quark\Extensions\JOSE\JWK\JWK;

/**
 * Class Apple
 *
 * @package Quark\Extensions\AppleSignIn
 */
class Apple implements IJOSEJWTIdentityProvider, IJOSEJWKProvider {
	const URL_KEYS = 'https://appleid.apple.com/auth/keys';

	/**
	 * @return bool
	 */
	public function JOSEJWTIdentityValidate () {
		return true;
	}

	/**
	 * @param object $payload
	 *
	 * @return IJOSEJWTIdentity
	 */
	public function JOSEJWTIdentity ($payload) {
		return new AppleID(
			isset($payload->email) ? $payload->email : null,
			isset($payload->sub) ? $payload->sub : null
		);
	}

	/**
	 * @param object $data
	 *
	 * @return JWK
	 */
	public function JOSEJWKProviderKeyExtract ($data) {
		if (!isset($data->kid)) return null;

		$response = QuarkHTTPClient::To(
			self::URL_KEYS,
			QuarkDTO::ForGET(),
			new QuarkDTO(new QuarkJSONIOProcessor())
		);

		if (!$response || !isset($response->keys) || !is_array($response->keys) || sizeof($response->keys) == 0) return null;

		foreach ($response->keys as $i => &$key)
			if ($key->kid == $data->kid)
				return JWK::FromData($key);

		return null;
	}
}