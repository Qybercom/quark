<?php
namespace Quark\Extensions\Quark\EncryptionAlgorithms;

use Quark\IQuarkEncryptionAlgorithm;

use Quark\QuarkEncryptionKey;
use Quark\QuarkEncryptionKeyDetails;

/**
 * Class EncryptionAlgorithmRSA
 *
 * @package Quark\Extensions\Quark\EncryptionAlgorithms
 */
class EncryptionAlgorithmRSA implements IQuarkEncryptionAlgorithm {
	const OPENSSL_TYPE = 'rsa';

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