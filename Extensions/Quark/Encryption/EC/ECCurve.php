<?php
namespace Quark\Extensions\Quark\Encryption\EC;

use Quark\QuarkMathNumber;

/**
 * Class ECCurve
 *
 * @package Quark\Extensions\Quark\Encryption\EC
 */
class ECCurve {
	const NAME_P_256 = 'P-256';
	const NAME_P_384 = 'P-384';
	const NAME_P_521 = 'P-521';

	const SIZE_P_256 = 256;
	const SIZE_P_384 = 384;
	const SIZE_P_521 = 521;

	const COMPONENT_P_256_P = 'ffffffff00000001000000000000000000000000ffffffffffffffffffffffff';
	const COMPONENT_P_256_A = 'ffffffff00000001000000000000000000000000fffffffffffffffffffffffc';
	const COMPONENT_P_256_B = '5ac635d8aa3a93e7b3ebbd55769886bc651d06b0cc53b0f63bce3c3e27d2604b';
	const COMPONENT_P_256_X = '6b17d1f2e12c4247f8bce6e563a440f277037d812deb33a0f4a13945d898c296';
	const COMPONENT_P_256_Y = '4fe342e2fe1a7f9b8ee7eb4a7c0f9e162bce33576b315ececbb6406837bf51f5';
	const COMPONENT_P_256_N = 'ffffffff00000000ffffffffffffffffbce6faada7179e84f3b9cac2fc632551';

	const COMPONENT_P_384_P = 'fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffeffffffff0000000000000000ffffffff';
	const COMPONENT_P_384_A = 'fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffeffffffff0000000000000000fffffffc';
	const COMPONENT_P_384_B = 'b3312fa7e23ee7e4988e056be3f82d19181d9c6efe8141120314088f5013875ac656398d8a2ed19d2a85c8edd3ec2aef';
	const COMPONENT_P_384_X = 'aa87ca22be8b05378eb1c71ef320ad746e1d3b628ba79b9859f741e082542a385502f25dbf55296c3a545e3872760ab7';
	const COMPONENT_P_384_Y = '3617de4a96262c6f5d9e98bf9292dc29f8f41dbd289a147ce9da3113b5f0b8c00a60b1ce1d7e819d7a431d7c90ea0e5f';
	const COMPONENT_P_384_N = 'ffffffffffffffffffffffffffffffffffffffffffffffffc7634d81f4372ddf581a0db248b0a77aecec196accc52973';

	const COMPONENT_P_521_P = '000001ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff';
	const COMPONENT_P_521_A = '000001fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffc';
	const COMPONENT_P_521_B = '00000051953eb9618e1c9a1f929a21a0b68540eea2da725b99b315f3b8b489918ef109e156193951ec7e937b1652c0bd3bb1bf073573df883d2c34f1ef451fd46b503f00';
	const COMPONENT_P_521_X = '000000c6858e06b70404e9cd9e3ecb662395b4429c648139053fb521f828af606b4d3dbaa14b5e77efe75928fe1dc127a2ffa8de3348b3c1856a429bf97e7e31c2e5bd66';
	const COMPONENT_P_521_Y = '0000011839296a789a3bc0045c8a5fb42c7d1bd998f54449579b446817afbd17273e662c97ee72995ef42640c550b9013fad0761353c7086a272c24088be94769fd16650';
	const COMPONENT_P_521_N = '000001fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffa51868783bf2f966b7fcc0148f709a5d03bb5c9b8899c47aebb6fb71e91386409';

	/**
	 * @var int[] $_sizes
	 */
	private static $_sizes = array(
		self::NAME_P_256 => self::SIZE_P_256,
		self::NAME_P_384 => self::SIZE_P_384,
		self::NAME_P_521 => self::SIZE_P_521
	);

	/**
	 * @var string[][] $_components
	 */
	private static $_components = array(
		self::NAME_P_256 => array(
			self::COMPONENT_P_256_P,
			self::COMPONENT_P_256_A,
			self::COMPONENT_P_256_B,
			self::COMPONENT_P_256_X,
			self::COMPONENT_P_256_Y,
			self::COMPONENT_P_256_N
		),
		self::NAME_P_384 => array(
			self::COMPONENT_P_384_P,
			self::COMPONENT_P_384_A,
			self::COMPONENT_P_384_B,
			self::COMPONENT_P_384_X,
			self::COMPONENT_P_384_Y,
			self::COMPONENT_P_384_N
		),
		self::NAME_P_521 => array(
			self::COMPONENT_P_521_P,
			self::COMPONENT_P_521_A,
			self::COMPONENT_P_521_B,
			self::COMPONENT_P_521_X,
			self::COMPONENT_P_521_Y,
			self::COMPONENT_P_521_N
		)
	);

	/**
	 * @var int $_size
	 */
	private $_size;

	/**
	 * @var QuarkMathNumber $_componentP
	 */
	private $_componentP;

	/**
	 * @var QuarkMathNumber $_componentA
	 */
	private $_componentA;

	/**
	 * @var QuarkMathNumber $_componentB
	 */
	private $_componentB;

	/**
	 * @var ECCurvePoint $_seed
	 */
	private $_seed;

	/**
	 * @param int $size = null
	 *
	 * @return int
	 */
	public function Size ($size = null) {
		if (func_num_args() != 0)
			$this->_size = $size;

		return $this->_size;
	}

	/**
	 * @param QuarkMathNumber $component = null
	 *
	 * @return QuarkMathNumber
	 */
	public function ComponentP (QuarkMathNumber $component = null) {
		if (func_num_args() != 0)
			$this->_componentP = $component;

		return $this->_componentP;
	}

	/**
	 * @param QuarkMathNumber $component = null
	 *
	 * @return QuarkMathNumber
	 */
	public function ComponentA (QuarkMathNumber $component = null) {
		if (func_num_args() != 0)
			$this->_componentA = $component;

		return $this->_componentA;
	}

	/**
	 * @param QuarkMathNumber $component = null
	 *
	 * @return QuarkMathNumber
	 */
	public function ComponentB (QuarkMathNumber $component = null) {
		if (func_num_args() != 0)
			$this->_componentB = $component;

		return $this->_componentB;
	}

	/**
	 * @param ECCurvePoint $seed = null
	 *
	 * @return ECCurvePoint
	 */
	public function Seed (ECCurvePoint $seed = null) {
		if (func_num_args() != 0)
			$this->_seed = $seed;

		return $this->_seed;
	}

	/**
	 * @param ECCurvePoint $point = null
	 * @param bool $infinity = false
	 *
	 * @return bool
	 */
	public function Contains (ECCurvePoint $point = null, $infinity = false) {
		if ($point == null) return false;
		if ($infinity && $point->Infinity()) return true;

		$buffer = $this->_componentA
			->Multiply($point->ComponentX())
			->Add($point->ComponentX()->Power(3))
			->Add($this->_componentB);

		$result = $point->ComponentY()
			->Power(2)
			->SubtractModular($buffer, $this->_componentP);

		return QuarkMathNumber::InitDecimal()->Equal($result);
	}

	/**
	 * @param QuarkMathNumber $x = null
	 * @param QuarkMathNumber $y = null
	 * @param QuarkMathNumber $n = null
	 *
	 * @return ECCurvePoint
	 */
	public function Point (QuarkMathNumber $x = null, QuarkMathNumber $y = null, QuarkMathNumber $n = null) {
		if ($x == null || $y == null) return null;

		$point = new ECCurvePoint($x, $y, $n);

		return $this->Contains($point) && ($n == null || $this->Multiply($point, $n)->Infinity()) ? $point : null;
	}

	/**
	 * @param ECCurvePoint $one = null
	 * @param ECCurvePoint $two = null
	 *
	 * @return ECCurvePoint
	 */
	public function Add (ECCurvePoint $one = null, ECCurvePoint $two = null) {
		if ($one == null || $two == null) return null;
		if ($two->Infinity()) return clone $one;
		if ($one->Infinity()) return clone $two;

		$oneX = $one->ComponentX();
		$oneY = $one->ComponentY();
		$twoX = $two->ComponentX();
		$twoY = $two->ComponentY();

		if ($oneX->Equal($twoX))
			return $oneY->Equal($twoY) ? $this->Double($one) : ECCurvePoint::InitInfinity();

		$slope = $twoY
			->Subtract($oneY)
			->DivideModular($twoX->Subtract($oneX), $this->_componentP);

		$xR = $slope
			->Power(2)
			->Subtract($oneX)
			->SubtractModular($twoX, $this->_componentP);

		$yR = $slope
			->Multiply($oneX->Subtract($xR))
			->SubtractModular($oneY, $this->_componentP);

		return $this->Point($xR, $yR, $one->ComponentN());
	}

	/**
	 * @param ECCurvePoint $point
	 * @param QuarkMathNumber $secret
	 *
	 * @return ECCurvePoint|null
	 */
	public function Multiply (ECCurvePoint $point, QuarkMathNumber $secret) {
		$zero = QuarkMathNumber::Zero();
		$infinity = ECCurvePoint::InitInfinity();

		echo '--- MULTIPLY:CHECK_INFINITY ---', "\r\n";
		if ($point->Infinity()) return $infinity;

		echo '--- MULTIPLY:CHECK_GT ---', "\r\n";
		//print_r();
		//var_dump($secret, $point->ComponentN());
		if ($gt = $point->ComponentN()->GreatThan($zero)) {
			echo '!!!', "\r\n";
			//var_dump(gmp_mod(gmp_init(5, 10), gmp_init(2, 10)));
			$secret = $secret->Modulo($point->ComponentN());
		}
		//var_dump($secret, $point->ComponentN());
		//var_dump($gt);

		echo '--- MULTIPLY:CHECK_ZERO ---', "\r\n";
		if ($secret->Equal($zero)) return $infinity;

		echo '--- MULTIPLY:INIT_R ---', "\r\n";
		/**
		 * @var ECCurvePoint[] $r
		 */
		$r = array($infinity, $point);
		$secretBinary = str_pad((string)$secret->BaseConvert(2), $this->_size, '0', STR_PAD_LEFT);
		$i = 0;
		$condition = null;
		var_dump($secretBinary);

		print_r($r);
		echo '--- MULTIPLY:CSWAP_START ---', "\r\n";

		while ($i < $this->_size) {
			$condition = $secretBinary[$i] ^ 1;

			$r[0] = $r[0]->ConditionalSwap($r[1], $condition);
			$r[0] = $this->Add($r[0], $r[1]);
			$r[1] = $this->Double($r[1]);
			$r[0] = $r[0]->ConditionalSwap($r[1], $condition);

			$i++;
		}

		echo '--- MULTIPLY:CSWAP_STOP ---', "\r\n";
		print_r($r[0]);

		unset($i, $condition, $secretBinary);

		return $this->Contains($r[0], true) ? $r[0] : null;
	}

	/**
	 * @param ECCurvePoint $point = null
	 *
	 * @return ECCurvePoint
	 */
	public function Double (ECCurvePoint $point = null) {
		if ($point == null) return null;
		if ($point->Infinity()) return ECCurvePoint::InitInfinity();//$point; // maybe pure infinity, see orig

		$tangent = QuarkMathNumber::InitDecimal(3)
			->Multiply($point->ComponentX()->Power(2))
			->Add($this->_componentA)
			->DivideModular(
				QuarkMathNumber::InitDecimal(2)->Multiply($point->ComponentY()),
				$this->_componentP
			);

		$x = $tangent
			->Power(2)
			->SubtractModular(
				QuarkMathNumber::InitDecimal(2)->Multiply($point->ComponentX()),
				$this->_componentP
			);

		$y = $tangent
			->Multiply($point->ComponentX()->Subtract($x))
			->SubtractModular(
				$point->ComponentY(),
				$this->_componentP
			);

		return new ECCurvePoint($x, $y, $point->ComponentN());
	}

	/**
	 * @param string $name = ''
	 *
	 * @return ECCurve
	 */
	public static function Init ($name = '') {
		if (!isset(self::$_sizes[$name])) return null;
		if (!isset(self::$_components[$name])) return null;

		$out = new self();

		$out->Size(self::$_sizes[$name]);
		$out->ComponentP(self::Component($name, 0));
		$out->ComponentA(self::Component($name, 1));
		$out->ComponentB(self::Component($name, 2));
		$out->Seed(new ECCurvePoint(
			self::Component($name, 3),
			self::Component($name, 4),
			self::Component($name, 5)
		));

		return $out;
	}

	/**
	 * @param string $name = ''
	 * @param int $i = 0
	 *
	 * @return QuarkMathNumber
	 */
	public static function Component ($name = '', $i = 0) {
		return isset(self::$_components[$name][$i]) ? QuarkMathNumber::InitHexadecimal(self::$_components[$name][$i]) : null;
	}
}