<?php
namespace Quark\Extensions\JOSE\Tokens;

use Quark\QuarkURI;

use Quark\Extensions\JOSE\IJOSEToken;
use Quark\Extensions\JOSE\JOSE;

/**
 * Class JWS
 *
 * @package Quark\Extensions\JOSE\Tokens
 */
class JWS implements IJOSEToken {
	/**
	 * @param JOSE $jose
	 *
	 * @return string
	 */
	public function JOSETokenCompactSerialize (JOSE &$jose) {
		$key = $jose->Key();
		if ($key == null) return null;

		$header = $key->Header()->Serialize();
		$payload = $jose->PayloadEncoded();

		return $header . '.' . $payload . '.' . QuarkURI::Base64Encode($jose->CompactSign($header . '.' . $payload));
	}

	/**
	 * @param JOSE $jose
	 * @param string $raw
	 *
	 * @return bool
	 */
	public function JOSETokenCompactUnserialize (JOSE &$jose, $raw) {
		// TODO: Implement JOSETokenCompactUnserialize() method.
	}

	/**
	 * @param JOSE $jose
	 *
	 * @return string
	 */
	public function JOSETokenJSONSerialize (JOSE &$jose) {
		// TODO: Implement JOSETokenJSONSerialize() method.
	}

	/**
	 * @param JOSE $jose
	 * @param string $raw
	 *
	 * @return bool
	 */
	public function JOSETokenJSONUnserialize (JOSE &$jose, $raw) {
		// TODO: Implement JOSETokenJSONUnserialize() method.
	}
}