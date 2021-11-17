<?php
namespace Quark\Extensions\JOSE;

use Quark\QuarkEncryptionKey;

/**
 * Class JOSEKey
 *
 * https://datatracker.ietf.org/doc/html/rfc7518
 *
 * @package Quark\Extensions\JOSE
 */
class JOSEKey {
	/**
	 * @var string[] $_params
	 */
	private static $_params = array(
		'Type' => 'kty',
		'UseTarget' => 'use',
		'Operations' => 'key_ops',
		'Algorithm' => 'alg',
		'ID' => 'kid',
		'CertificateURL' => 'x5u',
		'CertificateChain' => 'x5c',
		'CertificateThumbprint' => 'x5t',
		'CertificateThumbprintSHA256' => 'x5t#S256',

		'Curve' => 'crv',
		'CurveCoordinateX' => 'x',
		'CurveCoordinateY' => 'y',

		'ExponentPrivate' => 'd',

		'ExponentPublic' => 'e',
		'Modulus' => 'n',

		'FactorFirstPrime' => 'p',
		'FactorFirstExponent' => 'dp',
		'FactorSecondPrime' => 'q',
		'FactorSecondExponent' => 'dq',
		'FactorCoefficient' => 'qi',
		'FactorOther' => 'oth',

		'Key' => 'k'
	);

	/**
	 * @var IJOSEAlgorithm $_algorithmJOSE
	 */
	private $_algorithmJOSE;

	/**
	 * @var string $_type
	 */
	private $_type;

	/**
	 * @var string $_use
	 */
	private $_use;

	/**
	 * @var string[] $_operations = []
	 */
	private $_operations = array();

	/**
	 * @var string $_algorithm
	 */
	private $_algorithm;

	/**
	 * @var string $_id
	 */
	private $_id;

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
	 * @var string $_curve
	 */
	private $_curve;

	/**
	 * @var string $_curveCoordinateX
	 */
	private $_curveCoordinateX;

	/**
	 * @var string $_curveCoordinateY
	 */
	private $_curveCoordinateY;

	/**
	 * @var string $_exponentPrivate
	 */
	private $_exponentPrivate;

	/**
	 * @var string $_exponentPublic
	 */
	private $_exponentPublic;

	/**
	 * @var string $_modulus
	 */
	private $_modulus;

	/**
	 * @var string $_factorFirstPrime
	 */
	private $_factorFirstPrime;

	/**
	 * @var string $_factorFirstExponent
	 */
	private $_factorFirstExponent;

	/**
	 * @var string $_factorSecondPrime
	 */
	private $_factorSecondPrime;

	/**
	 * @var string $_factorSecondExponent
	 */
	private $_factorSecondExponent;

	/**
	 * @var string $_factorCoefficient
	 */
	private $_factorCoefficient;

	/**
	 * @var string $_factorOther
	 */
	private $_factorOther;

	/**
	 * @var string $_key
	 */
	private $_key;

	/**
	 * @var QuarkEncryptionKey $_keyOriginal
	 */
	private $_keyOriginal;

	/**
	 * @param IJOSEAlgorithm $algorithmJOSE = null
	 */
	public function __construct (IJOSEAlgorithm $algorithmJOSE = null) {
		$this->AlgorithmJOSE($algorithmJOSE);
	}

	/**
	 * @param IJOSEAlgorithm $algorithm = null
	 *
	 * @return IJOSEAlgorithm
	 */
	public function &AlgorithmJOSE (IJOSEAlgorithm $algorithm = null) {
		if (func_num_args() != 0)
			$this->_algorithmJOSE = $algorithm;

		return $this->_algorithmJOSE;
	}

	/**
	 * @param string $type = ''
	 *
	 * @return string
	 */
	public function Type ($type = '') {
		if (func_num_args() != 0)
			$this->_type = $type;

		return $this->_type;
	}

	/**
	 * @param string $use = ''
	 *
	 * @return string
	 */
	public function UseTarget ($use = '') {
		if (func_num_args() != 0)
			$this->_use = $use;

		return $this->_use;
	}

	/**
	 * @param string[] $operations = []
	 *
	 * @return string[]
	 */
	public function Operations ($operations = []) {
		if (func_num_args() != 0)
			$this->_operations = $operations;

		return $this->_operations;
	}

	/**
	 * @param string $algorithm = ''
	 *
	 * @return string
	 */
	public function Algorithm ($algorithm = '') {
		if (func_num_args() != 0)
			$this->_algorithm = $algorithm;

		return $this->_algorithm;
	}

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
	 * @param string $curve = ''
	 *
	 * @return string
	 */
	public function Curve ($curve = '') {
		if (func_num_args() != 0)
			$this->_curve = $curve;

		return $this->_curve;
	}

	/**
	 * @param string $coordinate = ''
	 *
	 * @return string
	 */
	public function CurveCoordinateX ($coordinate = '') {
		if (func_num_args() != 0)
			$this->_curveCoordinateX = $coordinate;

		return $this->_curveCoordinateX;
	}

	/**
	 * @param string $coordinate = ''
	 *
	 * @return string
	 */
	public function CurveCoordinateY ($coordinate = '') {
		if (func_num_args() != 0)
			$this->_curveCoordinateY = $coordinate;

		return $this->_curveCoordinateY;
	}

	/**
	 * @param string $exponent = ''
	 *
	 * @return string
	 */
	public function ExponentPrivate ($exponent = '') {
		if (func_num_args() != 0)
			$this->_exponentPrivate = $exponent;

		return $this->_exponentPrivate;
	}

	/**
	 * @param string $exponent = ''
	 *
	 * @return string
	 */
	public function ExponentPublic ($exponent = '') {
		if (func_num_args() != 0)
			$this->_exponentPublic = $exponent;

		return $this->_exponentPublic;
	}

	/**
	 * @param string $modulus = ''
	 *
	 * @return string
	 */
	public function Modulus ($modulus = '') {
		if (func_num_args() != 0)
			$this->_modulus = $modulus;

		return $this->_modulus;
	}

	/**
	 * @param string $prime = ''
	 *
	 * @return string
	 */
	public function FactorFirstPrime ($prime = '') {
		if (func_num_args() != 0)
			$this->_factorFirstPrime = $prime;

		return $this->_factorFirstPrime;
	}

	/**
	 * @param string $exponent = ''
	 *
	 * @return string
	 */
	public function FactorFirstExponent ($exponent = '') {
		if (func_num_args() != 0)
			$this->_factorFirstExponent = $exponent;

		return $this->_factorFirstExponent;
	}

	/**
	 * @param string $prime = ''
	 *
	 * @return string
	 */
	public function FactorSecondPrime ($prime = '') {
		if (func_num_args() != 0)
			$this->_factorSecondPrime = $prime;

		return $this->_factorSecondPrime;
	}

	/**
	 * @param string $exponent = ''
	 *
	 * @return string
	 */
	public function FactorSecondExponent ($exponent = '') {
		if (func_num_args() != 0)
			$this->_factorSecondExponent = $exponent;

		return $this->_factorSecondExponent;
	}

	/**
	 * @param string $coefficient = ''
	 *
	 * @return string
	 */
	public function FactorCoefficient ($coefficient = '') {
		if (func_num_args() != 0)
			$this->_factorCoefficient = $coefficient;

		return $this->_factorCoefficient;
	}

	/**
	 * @param string $other = ''
	 *
	 * @return string
	 */
	public function FactorOther ($other = '') {
		if (func_num_args() != 0)
			$this->_factorOther = $other;

		return $this->_factorOther;
	}

	/**
	 * @param string $key = ''
	 *
	 * @return string
	 */
	public function Key ($key = '') {
		if (func_num_args() != 0)
			$this->_key = $key;

		return $this->_key;
	}

	/**
	 * @param QuarkEncryptionKey $key = null
	 *
	 * @return QuarkEncryptionKey
	 */
	public function &KeyOriginal (QuarkEncryptionKey  $key = null) {
		if (func_num_args() != 0)
			$this->_keyOriginal = $key;

		return $this->_keyOriginal;
	}

	/**
	 * @param QuarkEncryptionKey $key = null
	 *
	 * @return bool
	 */
	public function Populate (QuarkEncryptionKey &$key = null) {
		if ($key == null) return false;

		$algorithm = JOSE::AlgorithmRecognize($key->Algorithm());
		if ($algorithm == null) return false;

		if (!$algorithm->JOSEAlgorithmKeyPopulate($this, $key)) return false;

		$this->AlgorithmJOSE($algorithm);
		$this->KeyOriginal($key);

		return true;
	}

	/**
	 * @return JOSEHeader
	 */
	public function Header () {
		return $this->_algorithmJOSE == null ? null : $this->_algorithmJOSE->JOSEAlgorithmHeader($this);
	}

	/**
	 * @param string $data = ''
	 *
	 * @return string
	 */
	public function CompactSign ($data = '') {
		return $this->_keyOriginal == null ? null : $this->_keyOriginal->Sign($data);
	}

	/**
	 * @param QuarkEncryptionKey $key = null
	 *
	 * @return JOSEKey
	 */
	public static function FromEncryptionKey (QuarkEncryptionKey $key = null) {
		if ($key == null) return null;

		$out = new self();

		return $out->Populate($key) ? $out : null;
	}
}