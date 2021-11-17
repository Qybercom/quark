<?php
namespace Quark\Extensions\JOSE;

use Quark\Extensions\JOSE\Algorithms\JOSEAlgorithmEC;
use Quark\Extensions\JOSE\Algorithms\JOSEAlgorithmRSA;
use Quark\Extensions\JOSE\Tokens\JWS;
use Quark\Extensions\Quark\EncryptionAlgorithms\EncryptionAlgorithmEC;
use Quark\Extensions\Quark\EncryptionAlgorithms\EncryptionAlgorithmRSA;
use Quark\IQuarkEncryptionAlgorithm;
use Quark\IQuarkExtension;
use Quark\QuarkEncryptionKey;
use Quark\QuarkURI;

/**
 * Class JOSE
 *
 * @package Quark\Extensions\JOSE
 */
class JOSE implements IQuarkExtension {
	const ALGORITHM_SHA256 = 'SHA256';
	const ALGORITHM_SHA384 = 'SHA384';
	const ALGORITHM_SHA512 = 'SHA512';

	/**
	 * @var IJOSEToken $_token
	 */
	private $_token;

	/**
	 * @var JOSEKey $_key
	 */
	private $_key;
	/**
	 * @var string $_payload
	 */
	private $_payload;

	/**
	 * @param IJOSEToken $token = null
	 * @param JOSEKey $key = null
	 */
	public function __construct (IJOSEToken $token = null, JOSEKey $key = null) {
		$this->Token($token);
		$this->Key($key);
	}

	/**
	 * @param IJOSEToken $token = null
	 *
	 * @return IJOSEToken
	 */
	public function &Token (IJOSEToken $token = null) {
		if (func_num_args() != 0)
			$this->_token = $token;

		return $this->_token;
	}

	/**
	 * @param JOSEKey $key = null
	 *
	 * @return JOSEKey
	 */
	public function &Key (JOSEKey $key = null) {
		if (func_num_args() != 0)
			$this->_key = $key;

		return $this->_key;
	}

	/**
	 * @param QuarkEncryptionKey $key = null
	 *
	 * @return JOSE
	 */
	public function KeyApply (QuarkEncryptionKey $key = null) {
		$this->Key(JOSEKey::FromEncryptionKey($key));

		return $this;
	}

	/**
	 * @param string $payload = ''
	 *
	 * @return string
	 */
	public function Payload ($payload = '') {
		if (func_num_args() != 0)
			$this->_payload = $payload;

		return $this->_payload;
	}

	/**
	 * @param JOSETokenClaim $claim = null
	 * @param bool $base64 = true
	 *
	 * @return JOSE
	 */
	public function PayloadClaim (JOSETokenClaim $claim = null, $base64 = true) {
		if ($claim != null)
			$this->_payload = $claim->CompactSerialize($base64);

		return $this;
	}

	/**
	 * @return string
	 */
	public function PayloadEncoded () {
		return QuarkURI::Base64Encode($this->_payload);
	}

	/**
	 * @param string $data = ''
	 *
	 * @return string
	 */
	public function CompactSign ($data = '') {
		return $this->_key->CompactSign($data);
	}

	/**
	 * @return string
	 */
	public function CompactSerialize () {
		return $this->_token->JOSETokenCompactSerialize($this);
	}

	/**
	 * @param string $raw = ''
	 *
	 * @return bool
	 */
	public function CompactUnserialize ($raw = '') {
		return $this->_token->JOSETokenCompactUnserialize($this, $raw);
	}

	/**
	 * @return string
	 */
	public function JSONSerialize () {
		return $this->_token->JOSETokenJSONSerialize($this);
	}

	/**
	 * @param string $raw = ''
	 *
	 * @return bool
	 */
	public function JSONUnserialize ($raw = '') {
		return $this->_token->JOSETokenJSONUnserialize($this, $raw);
	}

	/**
	 * @param JOSEKey $key = null
	 *
	 * @return JOSE
	 */
	public static function JWS (JOSEKey $key = null) {
		return new self(new JWS(), $key);
	}

	/**
	 * @param IQuarkEncryptionAlgorithm $algorithm = null
	 *
	 * @return IJOSEAlgorithm
	 */
	public static function AlgorithmRecognize (IQuarkEncryptionAlgorithm $algorithm = null) {
		if ($algorithm instanceof EncryptionAlgorithmEC)
			return new JOSEAlgorithmEC();

		if ($algorithm instanceof EncryptionAlgorithmRSA)
			return new JOSEAlgorithmRSA();

		return null;
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
	 * @deprecated
	 *
	 * @param string $input
	 *
	 * @return string
	 */
	public static function Base64Encode ($input) {
		return strtr(base64_encode($input), '+/', '-_');
	}

	/**
	 * @deprecated
	 *
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
}