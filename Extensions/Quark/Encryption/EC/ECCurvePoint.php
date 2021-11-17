<?php
namespace Quark\Extensions\Quark\Encryption\EC;

use Quark\QuarkMathNumber;

/**
 * Class ECCurvePoint
 *
 * @package Quark\Extensions\Quark\Encryption\EC
 */
class ECCurvePoint {
	/**
	 * @var QuarkMathNumber $_componentX
	 */
	private $_componentX;

	/**
	 * @var QuarkMathNumber $_componentY
	 */
	private $_componentY;

	/**
	 * @var QuarkMathNumber $_componentN
	 */
	private $_componentN;

	/**
	 * @var bool $_infinity = false
	 */
	private $_infinity = false;

	/**
	 * @param QuarkMathNumber $componentX = null
	 * @param QuarkMathNumber $componentY = null
	 * @param QuarkMathNumber $componentN = null
	 * @param bool $infinity = false
	 */
	public function __construct (QuarkMathNumber $componentX = null, QuarkMathNumber $componentY = null, QuarkMathNumber $componentN = null, $infinity = false) {
		$this->ComponentX($componentX);
		$this->ComponentY($componentY);
		$this->ComponentN($componentN);
		$this->Infinity($infinity);
	}

	/**
	 * @param QuarkMathNumber $component = null
	 *
	 * @return QuarkMathNumber
	 */
	public function ComponentX (QuarkMathNumber $component = null) {
		if (func_num_args() != 0)
			$this->_componentX = $component;

		return $this->_componentX;
	}

	/**
	 * @param QuarkMathNumber $component = null
	 *
	 * @return QuarkMathNumber
	 */
	public function ComponentY (QuarkMathNumber $component = null) {
		if (func_num_args() != 0)
			$this->_componentY = $component;

		return $this->_componentY;
	}

	/**
	 * @param QuarkMathNumber $component = null
	 *
	 * @return QuarkMathNumber
	 */
	public function ComponentN (QuarkMathNumber $component = null) {
		if (func_num_args() != 0)
			$this->_componentN = $component;

		return $this->_componentN;
	}

	/**
	 * @param bool $infinity = false
	 *
	 * @return bool
	 */
	public function Infinity ($infinity = false) {
		if (func_num_args() != 0)
			$this->_infinity = $infinity;

		return $this->_infinity;
	}

	/**
	 * https://datatracker.ietf.org/doc/html/rfc7748
	 * https://hal-cea.archives-ouvertes.fr/cea-03157323/document
	 *
	 * @param ECCurvePoint $target = null
	 * @param int $condition = 0
	 *
	 * @return ECCurvePoint
	 */
	public function ConditionalSwap (ECCurvePoint &$target = null, $condition = 0) {
		if ($target == null) return null;

		//print_r($this);
		$this->_componentX->BitwiseSwap($target->_componentX, $condition);
		$this->_componentY->BitwiseSwap($target->_componentY, $condition);
		$this->_componentN->BitwiseSwap($target->_componentN, $condition);
		//print_r($this);

		$infinity = $this->_infinity();
		$this->_infinity = $this->_infinity()->BitwiseSwap($infinity)->ToBool();
		$target->_infinity = $infinity->ToBool();

		$this->_componentX->BitwiseSwap($target->_componentX, $condition);

		return $this;
	}

	/**
	 * @return QuarkMathNumber
	 */
	private function _infinity () {
		return QuarkMathNumber::InitDecimal((int)$this->_infinity);
	}

	/**
	 * @return ECCurvePoint
	 */
	public static function InitInfinity () {
		return new self(
			QuarkMathNumber::Zero(),
			QuarkMathNumber::Zero(),
			QuarkMathNumber::Zero(),
			true
		);
	}
}