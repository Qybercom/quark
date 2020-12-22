<?php
namespace Quark\Extensions\JOSE\JWK;

use Quark\Extensions\JOSE\JWK\Providers\RSA;

/**
 * Class JWK
 *
 * @package Quark\Extensions\JOSE\JWK
 */
class JWK {
	const TYPE_EC = 'EC';
	const TYPE_RSA = 'RSA';
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
	 *
	 * @return object
	 */
	public function Data ($data = null) {
		if (func_num_args() != 0)
			$this->_data = $data;

		return $this->_data;
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

		if (isset($data->kty)) $out->Type($data->kty);
		if (isset($data->kid)) $out->ID($data->kid);
		if (isset($data->use)) $out->UseTarget($data->use);
		if (isset($data->key_ops)) $out->Operations($data->key_ops);
		if (isset($data->alg)) $out->Algorithm($data->alg);

		$provider = $out->Provider();

		if ($provider != null)
			$provider->JOSEJWKAlgorithmProviderRetrieve($out);

		return $out;
	}
}