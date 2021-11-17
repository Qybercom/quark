<?php
namespace Quark\Extensions\JOSE;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;

use Quark\Extensions\JOSE\JWA\HMAC;
use Quark\Extensions\JOSE\JWA\RSASSA;

use Quark\Extensions\JOSE\JWK\EC;
use Quark\Extensions\JOSE\JWK\RSA;

/**
 * Class JOSEConfig
 *
 * @package Quark\Extensions\JOSE
 */
class JOSEConfig implements IQuarkExtensionConfig {
	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var IJOSEProvider[] $_providers = []
	 */
	private $_providers = array();

	/**
	 * @var IJOSEJWAProvider[] $_jwa = []
	 */
	private $_jwa;

	/**
	 * @var IJOSEJWKProvider[] $_jwk = []
	 */
	private $_jwk;

	/**
	 * JOSEConfig constructor
	 */
	public function __construct () {
		$this->_jwa = array(
			new RSASSA(),
			new HMAC()
		);

		$this->_jwk = array(
			new RSA(),
			new EC()
		);
	}

	/**
	 * @return IJOSEJWAProvider[]
	 */
	public function &JWA () {
		return $this->_jwa;
	}

	/**
	 * @param IJOSEJWAProvider $jwa = null
	 *
	 * @return JOSEConfig
	 */
	public function JWARegister (IJOSEJWAProvider $jwa = null) {
		if ($jwa != null)
			$this->_jwa[] = $jwa;

		return $this;
	}

	/**
	 * @param string $cipher
	 *
	 * @return IJOSEJWAProvider
	 */
	public function JWAProviderByCipher ($cipher) {
		$out = null;
		$ciphers = array();

		foreach ($this->_jwa as $i => &$jwa) {
			$ciphers = $jwa->JOSEJWACipherList();

			if (isset($ciphers[$cipher])) $out = $jwa;
		}

		unset($i, $jwa, $ciphers);

		return $out;
	}

	/**
	 * @return IJOSEJWKProvider[]
	 */
	public function &JWK () {
		return $this->_jwk;
	}

	/**
	 * @param IJOSEJWKProvider $jwk = null
	 *
	 * @return JOSEConfig
	 */
	public function JWKRegister (IJOSEJWKProvider $jwk = null) {
		if ($jwk != null)
			$this->_jwk[] = $jwk;

		return $this;
	}

	/**
	 * @param string $type
	 * @param string $algorithm
	 *
	 * @return IJOSEJWKProvider
	 */
	public function JWKProviderByAlgorithm ($type, $algorithm) {
		$out = null;
		$algorithms = array();

		foreach ($this->_jwk as $i => &$jwk) {
			if ($jwk->JOSEJWKType() != $type) continue;

			$algorithms = $jwk->JOSEJWKAlgorithmList();

			if (isset($algorithms[$algorithm])) $out = $jwk;
		}

		unset($i, $jwk, $algorithms);

		return $out;
	}

	/**
	 * @param string $name
	 */
	public function Stacked ($name) {
		$this->_name = $name;
	}

	/**
	 * @return string
	 */
	public function ExtensionName () {
		return $this->_name;
	}

	/**
	 * @param object $ini
	 *
	 * @return mixed
	 */
	public function ExtensionOptions ($ini) {
		// TODO: Implement ExtensionOptions() method.
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new JOSE($this->_name);
	}
}