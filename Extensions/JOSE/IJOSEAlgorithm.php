<?php
namespace Quark\Extensions\JOSE;

use Quark\QuarkEncryptionKey;

/**
 * Interface IJOSEAlgorithm
 *
 * @package Quark\Extensions\JOSE
 */
interface IJOSEAlgorithm {
	/**
	 * @param JOSEKey $keyTarget
	 * @param QuarkEncryptionKey $keySource
	 *
	 * @return bool
	 */
	public function JOSEAlgorithmKeyPopulate(JOSEKey &$keyTarget, QuarkEncryptionKey &$keySource);

	/**
	 * @param JOSEKey $keySource
	 *
	 * @return JOSEHeader
	 */
	public function JOSEAlgorithmHeader(JOSEKey &$keySource);
}