<?php
namespace Quark\Extensions\JOSE;

use Quark\QuarkDate;
use Quark\QuarkURI;

/**
 * Class JOSETokenClaim
 *
 * https://github.com/Spomky-Labs/base64url/blob/v2.x/src/Base64Url.php
 * https://gist.github.com/nathggns/6652997
 * https://datatracker.ietf.org/doc/html/rfc7519#page-8
 *
 * @package Quark\Extensions\JOSE
 */
class JOSETokenClaim {
	/**
	 * @var string[] $_properties
	 */
	private static $_properties = array(
		'ID' => 'jti',
		'Audience' => 'aud',
		'DateEdgeEnd' => 'exp',
		'Issuer' => 'iss',
		'Subject' => 'sub',
		'DateIssued' => 'iat',
		'DateEdgeBegin' => 'nbf'
	);

	/**
	 * @var string[] $_propertiesDate
	 */
	private static $_propertiesDate = array(
		'DateIssued',
		'DateEdgeBegin',
		'DateEdgeEnd'
	);

	/**
	 * @var string[] $_propertiesArray
	 */
	private static $_propertiesArray = array(
		'Audience'
	);

	/**
	 * @var string $_id
	 */
	private $_id;

	/**
	 * @var string $_issuer
	 */
	private $_issuer;

	/**
	 * @var string $_subject
	 */
	private $_subject;

	/**
	 * @var string[] $_audience
	 */
	private $_audience;

	/**
	 * @var bool $_audienceForceSingle = false
	 */
	private $_audienceForceSingle = false;

	/**
	 * @var QuarkDate $_dateIssued
	 */
	private $_dateIssued;

	/**
	 * @var QuarkDate $_dateEdgeBegin
	 */
	private $_dateEdgeBegin;

	/**
	 * @var QuarkDate $_dateEdgeEnd
	 */
	private $_dateEdgeEnd;

	/**
	 * @var string[] $_claims = []
	 */
	private $_claims = array();

	/**
	 * @param string $id = ''
	 *
	 * @return string
	 */
	public function ID ($id = '') {
		if (func_num_args() != 0)
			$this->_id = $id;

		return $this->_id;
	}

	/**
	 * @param string $issuer = ''
	 *
	 * @return string
	 */
	public function Issuer ($issuer = '') {
		if (func_num_args() != 0)
			$this->_issuer = $issuer;

		return $this->_issuer;
	}

	/**
	 * @param string $subject = ''
	 *
	 * @return string
	 */
	public function Subject ($subject = '') {
		if (func_num_args() != 0)
			$this->_subject = $subject;

		return $this->_subject;
	}

	/**
	 * @param string[] $audience = []
	 *
	 * @return string[]
	 */
	public function Audience ($audience = []) {
		if (func_num_args() != 0)
			$this->_audience = $audience;

		return $this->_audience;
	}

	/**
	 * @param bool $force = false
	 *
	 * @return bool
	 */
	public function AudienceForceSingle ($force = false) {
		if (func_num_args() != 0)
			$this->_audienceForceSingle = $force;

		return $this->_audienceForceSingle;
	}

	/**
	 * @param QuarkDate $date = null
	 *
	 * @return QuarkDate
	 */
	public function DateIssued (QuarkDate $date = null) {
		if (func_num_args() != 0)
			$this->_dateIssued = $date;

		return $this->_dateIssued;
	}

	/**
	 * @param QuarkDate $date = null
	 *
	 * @return QuarkDate
	 */
	public function DateEdgeBegin (QuarkDate $date = null) {
		if (func_num_args() != 0)
			$this->_dateEdgeBegin = $date;

		return $this->_dateEdgeBegin;
	}

	/**
	 * @param QuarkDate $date = null
	 *
	 * @return QuarkDate
	 */
	public function DateEdgeEnd (QuarkDate $date = null) {
		if (func_num_args() != 0)
			$this->_dateEdgeEnd = $date;

		return $this->_dateEdgeEnd;
	}

	/**
	 * @return string[]
	 */
	public function Claims () {
		return $this->_claims;
	}

	/**
	 * @param string $key = ''
	 * @param $value = null
	 *
	 * @return JOSETokenClaim
	 */
	public function Claim ($key = '', $value = null) {
		if (func_num_args() != 0)
			$this->_claims[$key] = $value;

		return $this;
	}

	/**
	 * @param bool $base64
	 *
	 * @return string
	 */
	public function CompactSerialize ($base64 = true) {
		$out = array();
		$buffer = null;

		foreach (self::$_properties as $property => &$field) {
			$buffer = $this->$property();

			if ($buffer instanceof QuarkDate)
				$buffer = $buffer->Timestamp();

			if ($buffer !== null)
				$out[$field] = $buffer;
		}

		unset($property, $field, $buffer);

		$properties = array_values(self::$_properties);
		foreach ($this->_claims as $key => &$value)
			if (!in_array($key, $properties))
				$out[$key] = $value;

		unset($key, $value, $properties);

		$buffer = JOSE::JSONEncode($out);
		return $base64 ? QuarkURI::Base64Encode($buffer) : $buffer;
	}

	/**
	 * @param string $raw = ''
	 * @param bool $base64 = true
	 *
	 * @return bool
	 */
	public function CompactUnserialize ($raw = '', $base64 = true) {
		$data = JOSE::JSONDecode($base64 ? QuarkURI::Base64Decode($raw) : $raw);
		if (!is_object($data)) return false;

		$buffer = null;

		foreach (self::$_properties as $property => &$field) {
			if (!isset($data->$field)) continue;

			$buffer = $data->$field;

			if (in_array($property, self::$_propertiesDate))
				$buffer = QuarkDate::FromTimestamp($buffer);

			if (in_array($property, self::$_propertiesArray))
				$buffer = array($buffer);

			$this->$property($buffer);
		}

		unset($property, $field, $buffer);

		$properties = array_values(self::$_properties);
		foreach ($data as $key => &$value)
			if (!in_array($key, $properties))
				$this->_claims[$key] = $value;

		unset($key, $value, $properties);

		return true;
	}
}