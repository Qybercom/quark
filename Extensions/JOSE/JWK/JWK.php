<?php
namespace Quark\Extensions\JOSE\JWK;

use Quark\Extensions\JOSE\JWK\Providers\EC;
use Quark\Extensions\JOSE\JWK\Providers\RSA;
use Quark\Extensions\JOSE\JWT;

/**
 * Class JWK
 *
 * https://github.com/web-push-libs/web-push-php/blob/d87e9e3034ca2b95b1822b1b335e7761c14b89f6/src/Utils.php#L58
 *
 * @package Quark\Extensions\JOSE\JWK
 */
class JWK {
	const TYPE_EC = 'ec';
	const TYPE_RSA = 'rsa';
	const TYPE_OCT = 'oct';

	const USE_SIG = 'sig';
	const USE_ENC = 'enc';

	/**
	 * @var string $_type
	 */
	private $_type;

	/**
	 * @var string $_id
	 */
	private $_id;

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
	 * @var string $_content
	 */
	private $_content;

	/**
	 * @var object $_data
	 */
	private $_data;

	/**
	 * @var string $_exponentPublic
	 */
	private $_exponentPublic;

	/**
	 * @var string $_exponentPrivate
	 */
	private $_exponentPrivate;

	/**
	 * @var string $_modulus
	 */
	private $_modulus;

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
	 * @param string $content = ''
	 *
	 * @return string
	 */
	public function Content ($content = '') {
		if (func_num_args() != 0)
			$this->_content = $content;

		return $this->_content;
	}

	/**
	 * @param object $data = null
	 * @param bool $populate = true
	 *
	 * @return object
	 */
	public function Data ($data = null, $populate = true) {
		if (func_num_args() != 0) {
			$this->_data = $data;

			if ($populate)
				$this->Populate($data);
		}

		return $this->_data;
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
	 * @return RSA
	 */
	public function Provider () {
		return self::ProviderByType($this->_type);
	}

	/**
	 * @param string $type
	 *
	 * @return RSA
	 */
	public static function ProviderByType ($type) {
		if ($type == self::TYPE_RSA)
			return new RSA();

		if ($type == self::TYPE_EC)
			return new EC();

		return null;
	}

	/**
	 * @param object $data
	 *
	 * @return JWK
	 */
	public static function FromData ($data) {
		$out = new self();

		$out->Data($data);

		$provider = $out->Provider();

		if ($provider != null)
			$provider->JOSEJWKAlgorithmProviderRetrieve($out);

		return $out;
	}

	/**
	 * @return JWK
	 */
	public function Generate () {
		$provider = $this->Provider();

		if ($provider != null)
			$provider->JOSEJWKAlgorithmProviderGenerate($this);

		return $this;
	}

	/**
	 * @param object $data = null
	 *
	 * @return JWK
	 */
	public function Populate ($data = null) {
		if (isset($data->kty)) $this->Type($data->kty);
		if (isset($data->kid)) $this->ID($data->kid);
		if (isset($data->use)) $this->UseTarget($data->use);
		if (isset($data->key_ops)) $this->Operations($data->key_ops);
		if (isset($data->alg)) $this->Algorithm($data->alg);
		if (isset($data->e)) $this->ExponentPublic($data->e);
		if (isset($data->d)) $this->ExponentPrivate($data->d);
		if (isset($data->n)) $this->Modulus($data->n);
		if (isset($data->crv)) $this->Curve($data->crv);
		if (isset($data->x)) $this->CurveCoordinateX($data->x);
		if (isset($data->y)) $this->CurveCoordinateY($data->y);

		return $this;
	}

	/**
	 * @param bool $bin = true
	 * @param bool $base64 = true
	 *
	 * @return string
	 */
	public function SerializePublicKey ($bin = true, $base64 = true) {
		$out =  '04'
			. str_pad(bin2hex(JWT::Base64Decode($this->CurveCoordinateX())), 64, '0', STR_PAD_LEFT)
			. str_pad(bin2hex(JWT::Base64Decode($this->CurveCoordinateY())), 64, '0', STR_PAD_LEFT);

		return self::_serializeKey($out, $bin, $base64);
	}

	/**
	 * @param bool $bin = true
	 * @param bool $base64 = true
	 *
	 * @return string
	 */
	public function SerializePrivateKey ($bin = true, $base64 = true) {
		$out = str_pad(bin2hex(JWT::Base64Decode($this->ExponentPrivate())), 64, '0', STR_PAD_LEFT);

		return self::_serializeKey($out, $bin, $base64);
	}

	/**
	 * @param string $key = ''
	 * @param bool $bin = true
	 * @param bool $base64 = true
	 *
	 * @return string
	 */
	private static function _serializeKey ($key = '', $bin = true, $base64 = true) {
		$out = $key;

		if ($bin) {
			$out = hex2bin($out);

			if ($base64)
				$out = JWT::Base64Encode($out);
		}

		return $out;
	}
}