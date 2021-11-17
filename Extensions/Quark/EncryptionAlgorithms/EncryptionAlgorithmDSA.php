<?php
namespace Quark\Extensions\Quark\EncryptionAlgorithms;

use Quark\IQuarkEncryptionAlgorithm;

use Quark\QuarkEncryptionKey;
use Quark\QuarkEncryptionKeyDetails;

/**
 * Class EncryptionAlgorithmDSA
 *
 * @package Quark\Extensions\Quark\EncryptionAlgorithms
 */
class EncryptionAlgorithmDSA implements IQuarkEncryptionAlgorithm {
	const OPENSSL_TYPE = 'dsa';

	/**
	 * @return bool
	 */
	public function EncryptionAlgorithmKeySymmetric () {
		return true;
	}

	/**
	 * @return QuarkEncryptionKeyDetails
	 * @var QuarkEncryptionKey $key
	 *
	 */
	public function EncryptionAlgorithmKeyDetails (QuarkEncryptionKey &$key) {
		return $key->DetailsOpenSSLAsymmetric(self::OPENSSL_TYPE);
	}
}