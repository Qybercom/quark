<?php
namespace Quark\Extensions\JOSE;

use Quark\QuarkURI;

/**
 * Class JOSEHeader
 *
 * @package Quark\Extensions\JOSE
 */
class JOSEHeader {
	const TYPE_JWT = 'JWT';

	const CONTENT_TYPE_JWT = 'JWT';

	/**
	 * @var string[] $_properties
	 */
	private static $_properties = array(
		'Type' => 'typ',
		'ContentType' => 'cty',
		'Algorithm' => 'alg',
		'Key' => 'jwk',
		'KeyURL' => 'jku',
		'KeyID' => 'kid',
		'CertificateURL' => 'x5u',
		'CertificateChain' => 'x5c',
		'CertificateThumbprint' => 'x5t',
		'CertificateThumbprintSHA256' => 'x5t#s256',
		'Critical' => 'crit',
		'Encoding' => 'enc',
		'ZIP' => 'zip'
	);

	/**
	 * @var string $_raw = ''
	 */
	private $_raw = '';

	/**
	 * @var string $_type = self::TYPE_JWT
	 */
	private $_type = self::TYPE_JWT;

	/**
	 * @var string $_contentType
	 */
	private $_contentType;

	/**
	 * @var string $_algorithm
	 */
	private $_algorithm;

	/**
	 * @var JOSEKey $_key
	 */
	private $_key;

	/**
	 * @var string $_keyURL
	 */
	private $_keyURL;

	/**
	 * @var string $_keyID
	 */
	private $_keyID;

	/**
	 * @var string $_certificateURL
	 */
	private $_certificateURL;

	/**
	 * @var string $_certificateChain
	 */
	private $_certificateChain;

	/**
	 * @var string $_certificateThumbprint
	 */
	private $_certificateThumbprint;

	/**
	 * @var string $_certificateThumbprintSHA256
	 */
	private $_certificateThumbprintSHA256;

	/**
	 * @var string[] $_critical
	 */
	private $_critical;

	/**
	 * @param string $raw = ''
	 *
	 * @return string
	 */
	public function Raw ($raw = '') {
		if (func_num_args() != 0)
			$this->_raw = $raw;

		return $this->_raw;
	}

	/**
	 * @param string $type = null
	 *
	 * @return string
	 */
	public function Type ($type = null) {
		if (func_num_args() != 0)
			$this->_type = $type;

		return $this->_type;
	}

	/**
	 * @param string $contentType = null
	 *
	 * @return string
	 */
	public function ContentType ($contentType = null) {
		if (func_num_args() != 0)
			$this->_contentType = $contentType;

		return $this->_contentType;
	}

	/**
	 * @param string $algorithm = null
	 *
	 * @return string
	 */
	public function Algorithm ($algorithm = null) {
		if (func_num_args() != 0)
			$this->_algorithm = $algorithm;

		return $this->_algorithm;
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
	 * @param string $url = null
	 *
	 * @return string
	 */
	public function KeyURL ($url = null) {
		if (func_num_args() != 0)
			$this->_keyURL = $url;

		return $this->_keyURL;
	}

	/**
	 * @param string $id = null
	 *
	 * @return string
	 */
	public function KeyID ($id = null) {
		if (func_num_args() != 0)
			$this->_keyID = $id;

		return $this->_keyID;
	}

	/**
	 * @param string $url = ''
	 *
	 * @return string
	 */
	public function CertificateURL ($url = '') {
		if (func_num_args() != 0)
			$this->_certificateURL = $url;

		return $this->_certificateURL;
	}

	/**
	 * @param string $chain = ''
	 *
	 * @return string
	 */
	public function CertificateChain ($chain = '') {
		if (func_num_args() != 0)
			$this->_certificateChain = $chain;

		return $this->_certificateChain;
	}

	/**
	 * @param string $thumbprint = ''
	 *
	 * @return string
	 */
	public function CertificateThumbprint ($thumbprint = '') {
		if (func_num_args() != 0)
			$this->_certificateThumbprint = $thumbprint;

		return $this->_certificateThumbprint;
	}

	/**
	 * @param string $thumbprint = ''
	 *
	 * @return string
	 */
	public function CertificateThumbprintSHA256 ($thumbprint = '') {
		if (func_num_args() != 0)
			$this->_certificateThumbprintSHA256 = $thumbprint;

		return $this->_certificateThumbprintSHA256;
	}

	/**
	 * @param string[] $critical = []
	 *
	 * @return string[]
	 */
	public function Critical ($critical = []) {
		if (func_num_args() != 0)
			$this->_critical = $critical;

		return $this->_critical;
	}
	/**
	 * @param bool $base64 = true
	 *
	 * @return string
	 */
	public function Serialize ($base64 = true) {
		$out = array();
		$value = null;

		foreach (self::$_properties as $property => &$field) {
			if (!method_exists($this, $property)) continue;
			$value = $this->$property();

			if ($value !== null)
				$out[$field] = $value;
		}

		unset($property, $field, $value, $properties);

		$buffer = JOSE::JSONEncode($out);
		return $base64 ? QuarkURI::Base64Encode($buffer) : $buffer;
	}

	/**
	 * @param string $raw
	 * @param bool $base64 = true
	 *
	 * @return bool
	 */
	public function Unserialize ($raw, $base64 = true) {
		$this->Raw($raw);

		$data = JOSE::JSONDecode($base64 ? QuarkURI::Base64Decode($raw) : $raw);

		foreach (self::$_properties as $property => &$field) {
			if (!isset($data->$field)) continue;
			if (!method_exists($this, $property)) continue;

			$this->$property($data->$field);
		}

		unset($property, $field, $properties, $data);

		return true;
	}
}