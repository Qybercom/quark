<?php
namespace Quark\Extensions\JOSE;

/**
 * Interface IJOSEToken
 *
 * @package Quark\Extensions\JOSE
 */
interface IJOSEToken {
	/**
	 * @param JOSE $jose
	 *
	 * @return string
	 */
	public function JOSETokenCompactSerialize(JOSE &$jose);

	/**
	 * @param JOSE $jose
	 * @param string $raw
	 *
	 * @return bool
	 */
	public function JOSETokenCompactUnserialize(JOSE &$jose, $raw);

	/**
	 * @param JOSE $jose
	 *
	 * @return string
	 */
	public function JOSETokenJSONSerialize(JOSE &$jose);

	/**
	 * @param JOSE $jose
	 * @param string $raw
	 *
	 * @return bool
	 */
	public function JOSETokenJSONUnserialize(JOSE &$jose, $raw);
}