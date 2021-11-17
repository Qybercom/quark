<?php
namespace Quark\Extensions\JOSE;

/**
 * Trait JOSETokenBehavior
 *
 * @package Quark\Extensions\JOSE
 */
trait JOSETokenBehavior {
	/**
	 * @var IJOSETokenHeader $_header
	 */
	private $_header;

	/**
	 * @var string $_payload
	 */
	private $_payload;

	/**
	 * @var JOSETokenClaim $_payloadJWT
	 */
	private $_payloadJWT;

	/**
	 * @var string $_signature = ''
	 */
	private $_signature = '';

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
	 * @param string $signature = ''
	 *
	 * @return string
	 */
	public function Signature ($signature = '') {
		if (func_num_args() != 0)
			$this->_signature = $signature;

		return $this->_signature;
	}

	/**
	 * @return IJOSETokenHeader
	 */
	public function &JOSETokenHeader () {
		return $this->_header;
	}

	/**
	 * @return JOSETokenClaim
	 */
	public function JOSETokenJWT () {
		return $this->_payloadJWT;
	}
}