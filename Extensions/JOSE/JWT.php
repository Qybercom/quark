<?php
namespace Quark\Extensions\JOSE;

use Quark\QuarkArchException;

use Quark\Extensions\JOSE\JWA\IJOSEJWAAlgorithmProvider;
use Quark\Extensions\JOSE\JWA\Providers\HashHMAC;
use Quark\Extensions\JOSE\JWA\Providers\OpenSSL;

use Quark\Extensions\JOSE\JWK\IJOSEJWKProvider;
use Quark\Extensions\JOSE\JWK\JWK;

/**
 * Class JWT
 *
 * https://github.com/Spomky-Labs/base64url/blob/v2.x/src/Base64Url.php
 * https://gist.github.com/nathggns/6652997
 *
 * @package Quark\Extensions\JOSE
 */
class JWT {
	const CIPHER_SHA256 = 'SHA256';
	const CIPHER_SHA384 = 'SHA384';
	const CIPHER_SHA512 = 'SHA512';

	/**
	 * @var IJOSEJWAAlgorithmProvider[] $_jwa = null
	 */
	private static $_jwa;

	/**
	 * @var string $_headerRaw = ''
	 */
	private $_headerRaw = '';

	/**
	 * @var object $_headerData = null
	 */
	private $_headerData = null;

	/**
	 * @var string $_payloadRaw = ''
	 */
	private $_payloadRaw = '';

	/**
	 * @var object $_payloadData = null
	 */
	private $_payloadData = null;

	/**
	 * @var string $_signatureRaw = ''
	 */
	private $_signatureRaw = '';

	/**
	 * @var string $_signatureData = null
	 */
	private $_signatureData = null;

	/**
	 * @var int $_timeout = 0
	 */
	private $_timeout = 0;

	/**
	 * @var int $_timestamp = null
	 */
	private $_timestamp = null;

	/**
	 * @param int $timeout = 0
	 *
	 * @return int
	 */
	public function Timeout ($timeout = 0) {
		if (func_num_args() != 0)
			$this->_timeout = $timeout;

		return $this->_timeout;
	}

	/**
	 * @param int $timestamp = null
	 *
	 * @return int
	 */
	public function Timestamp ($timestamp = null) {
		if (func_num_args() != 0)
			$this->_timestamp = $timestamp;

		return $this->_timestamp;
	}

	/**
	 * @param IJOSEJWKProvider $provider = null
	 *
	 * @return JWK
	 */
	public function JWKRetrieve (IJOSEJWKProvider $provider = null) {
		return $provider == null ? null : $provider->JOSEJWKProviderKeyExtract($this->_headerData);
	}

	/**
	 * @param JWK $jwk = null
	 *
	 * @return JWK
	 */
	public function JWKGenerate (JWK $jwk = null) {
		return $jwk == null ? null : $jwk->Generate();
	}

	/**
	 * @return IJOSEJWAAlgorithmProvider
	 */
	public function JWARetrieve () {
		return isset($this->_headerData->alg) ? self::JWAByName($this->_headerData->alg) : null;
	}

	/**
	 * @param IJOSEJWTIdentityProvider $provider = null
	 *
	 * @return IJOSEJWTIdentity
	 *
	 * @throws QuarkArchException
	 */
	public function Identity (IJOSEJWTIdentityProvider $provider = null) {
		if ($provider == null) return null;

		if ($provider->JOSEJWTIdentityValidate()) {
			if (!($provider instanceof IJOSEJWKProvider))
				throw new QuarkArchException('Class ' . get_class($provider) . ' must be IJOSEJWKProvider');

			if (!$this->Valid($provider))
				return null;
		}

		return $provider->JOSEJWTIdentity($this->_payloadData);
	}

	/**
	 * @param string $token = ''
	 *
	 * @return JWT
	 */
	public static function FromToken ($token = '') {
		$parts = explode('.', $token);
		if (sizeof($parts) != 3) return null;

		$jwt = new self();

		$jwt->_headerRaw = $parts[0];
		$jwt->_headerData = json_decode(self::Base64Decode($jwt->_headerRaw));

		$jwt->_payloadRaw = $parts[1];
		$jwt->_payloadData = json_decode(self::Base64Decode($jwt->_payloadRaw));

		$jwt->_signatureRaw = $parts[2];
		$jwt->_signatureData = self::Base64Decode($jwt->_signatureRaw);

		return $jwt;
	}

	/**
	 * @param IJOSEJWKProvider $provider = null
	 *
	 * @return bool
	 */
	public function Valid (IJOSEJWKProvider $provider = null) {
		return $this->ValidTime() && $this->ValidSignature($provider);
	}

	/**
	 * @param IJOSEJWKProvider $provider = null
	 *
	 * @return bool
	 */
	public function ValidSignature (IJOSEJWKProvider $provider = null) {
		$jwk = $this->JWKRetrieve($provider);
		if ($jwk == null) return false;

		$jwa = $this->JWARetrieve();
		if ($jwa == null) return false;

		return $jwa->JOSEJWAAlgorithmSignatureCheck(
			$this->_headerRaw . '.' . $this->_payloadRaw,
			$this->_signatureData,
			$jwk->Content(),
			$jwa->JOSEJWAAlgorithmCipher($this->_headerData->alg)
		);
	}

	/**
	 * @return bool
	 */
	public function ValidTime () {
		return self::VerifyTime($this->_payloadData, $this->_timeout, $this->_timestamp);
	}

	/**
	 * @param $payload = null
	 * @param int $timeout = 0
	 * @param int $timestamp = null
	 *
	 * @return bool
	 */
	public static function VerifyTime ($payload = null, $timeout = 0, $timestamp = null) {
		if ($timestamp == null)
			$timestamp = time();

		$edge = $timestamp + $timeout;

		if (isset($payload->nbf) && $payload->nbf > $edge) return false; // allowed usage time
		if (isset($payload->iat) && $payload->iat > $edge) return false; // token created in past
		if (isset($payload->exp) && ($timestamp - $timeout) >= $payload->exp) return false; // token is not expired

		return true;
	}

	/**
	 * @param $data
	 *
	 * @return string
	 */
	public static function JSONEncode ($data = null) {
		return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	/**
	 * @param string $data
	 *
	 * @return mixed
	 */
	public static function JSONDecode ($data = '') {
		return json_decode($data, false, 512, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	/**
	 * @param string $input
	 *
	 * @return string
	 */
	public static function Base64Encode ($input) {
		return strtr(base64_encode($input), '+/', '-_');
	}

	/**
	 * @param string $input
	 *
	 * @return string
	 */
    public static function Base64Decode ($input) {
        $remainder = strlen($input) % 4;

		if ($remainder)
            $input .= str_repeat('=', 4 - $remainder);

        return base64_decode(strtr($input, '-_', '+/'));
    }

	/**
	 * @param string $name
	 *
	 * @return IJOSEJWAAlgorithmProvider
	 */
	public static function JWAByName ($name = '') {
		if (self::$_jwa == null)
			self::$_jwa = array(
				new OpenSSL(),
				new HashHMAC()
			);

		foreach (self::$_jwa as $i => &$jwa)
			if ($jwa->JOSEJWAAlgorithmCipher($name)) return $jwa;

		return null;
	}
}