<?php
namespace Quark\Extensions\Quark\Encryption\EC;

use Quark\QuarkException;
use Quark\QuarkMathNumber;
use Quark\QuarkURI;

/**
 * Class ECKey
 *
 * @package Quark\Extensions\Quark\Encryption\EC
 */
class ECKey {
	const ENCODING_8BIT = '8bit';

	/**
	 * @var string $_componentX = ''
	 */
	private $_componentX = '';

	/**
	 * @var string $_componentY = ''
	 */
	private $_componentY = '';

	/**
	 * @var string $_componentD = ''
	 */
	private $_componentD = '';

	/**
	 * @param string $component = ''
	 *
	 * @return string
	 */
	public function ComponentX ($component = '') {
		if (func_num_args() != 0)
			$this->_componentX = $component;

		return $this->_componentX;
	}

	/**
	 * @param string $component = ''
	 *
	 * @return string
	 */
	public function ComponentY ($component = '') {
		if (func_num_args() != 0)
			$this->_componentY = $component;

		return $this->_componentY;
	}

	/**
	 * @param string $component = ''
	 *
	 * @return string
	 */
	public function ComponentD ($component = '') {
		if (func_num_args() != 0)
			$this->_componentD = $component;

		return $this->_componentD;
	}

	/**
	 * @param string $curveName = ''
	 *
	 * @return ECKey
	 */
	public static function Generate ($curveName = '') {
		$curve = ECCurve::Init($curveName);
		if ($curve == null) return null;

		//echo '--- CURVE ---', "\r\n";
		//print_r($curve);
		$secret = self::GeneratePrivate($curve->Seed()->ComponentN());
		//echo '--- SECRET ---', "\r\n";
		//print_r($secret);
		$public = $curve->Multiply($curve->Seed(), $secret);
		echo '--- PUBLIC---', "\r\n";
		print_r($public);

		$out = new self();

		$out->ComponentX(self::ComponentBase64Encode($public->ComponentX()));
		$out->ComponentY(self::ComponentBase64Encode($public->ComponentY()));
		$out->ComponentD(self::ComponentBase64Encode($secret));

		return $out;
	}

	/**
	 * @param QuarkMathNumber $max = null
	 *
	 * @return QuarkMathNumber
	 */
	public static function GeneratePrivate (QuarkMathNumber $max = null) {
		if (func_num_args() == 0) $max = QuarkMathNumber::Max();
		if ($max == null) return null;

		$result = QuarkMathNumber::InitDecimal();
		$mask = QuarkMathNumber::InitDecimal(2)
			->Power($max->LengthBits())
			->SubtractPrimitive(1);

		//echo '--- MAX ---', "\r\n";
		//print_r($max);
		//echo '--- MASK ---', "\r\n";
		//print_r($mask);

		$random = openssl_random_pseudo_bytes($lb = $max->LengthBytes());
		$randomLen = mb_strlen($random, self::ENCODING_8BIT);

		//echo '--- RANDOM, RANDOMLENGTH, LB ---', "\r\n";
		//var_dump($random, $randomLen, $lb);

		$i = 0;
		while ($i < $randomLen) {
			$result = $result
				->MultiplyPrimitive(256)
				->AddPrimitive(ord($random[$i]));

			$i++;
		}

		//echo '--- LOOP ---', "\r\n";
		//var_dump($result);

		return $result->BitwiseAnd($mask);
	}

	/**
	 * https://github.com/web-push-libs/web-push-php/commit/d914e9b477a6a2d853b40433dc882f022d2dd588#diff-3d429bb1a875d7c1e944b7cc970c8bd25f97a8f83c2d45e3bfccf64903ccbcadR155
	 *
	 * @param QuarkMathNumber $number = null
	 *
	 * @return string
	 */
	public static function ComponentBase64Encode (QuarkMathNumber $number = null) {
		echo '--- COMPONENT BASE64 ENCODE ---', "\r\n";
		$val = (string)$number->Serialize(16);
		$valLen = strlen($val);

		if ($valLen < 64) {
			$val = str_pad($val, 64, 0, STR_PAD_LEFT);
			var_dump('[WARN] len(val) < 64:', $valLen, $val);
		}

		return QuarkURI::Base64Encode(str_pad(hex2bin($val), 32, chr(0), STR_PAD_LEFT));
	}
}