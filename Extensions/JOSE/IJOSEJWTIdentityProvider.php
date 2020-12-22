<?php
namespace Quark\Extensions\JOSE;

/**
 * Interface IJOSEJWTIdentityProvider
 *
 * @package Quark\Extensions\JOSE
 */
interface IJOSEJWTIdentityProvider {
	/**
	 * @return bool
	 */
	public function JOSEJWTIdentityValidate();

	/**
	 * @param object $payload
	 *
	 * @return IJOSEJWTIdentity
	 */
	public function JOSEJWTIdentity($payload);
}