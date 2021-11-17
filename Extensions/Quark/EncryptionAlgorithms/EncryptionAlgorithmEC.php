<?php
namespace Quark\Extensions\Quark\EncryptionAlgorithms;

use Quark\IQuarkEncryptionAlgorithm;

use Quark\Quark;
use Quark\QuarkEncryptionKey;
use Quark\QuarkEncryptionKeyDetails;
use Quark\QuarkMathNumber;
use Quark\QuarkPEMDTO;
use Quark\QuarkPEMIOProcessor;

/**
 * Class EncryptionAlgorithmEC
 *
 * https://www.oreilly.com/library/view/programming-bitcoin/9781492031482/ch04.html
 * https://wiki.openssl.org/index.php/Command_Line_Elliptic_Curve_Operations
 * https://github.com/web-token/jwt-core/blob/master/Util/ECKey.php
 *
 * @package Quark\Extensions\Quark\EncryptionAlgorithms
 */
class EncryptionAlgorithmEC implements IQuarkEncryptionAlgorithm {
	const OPENSSL_TYPE = 'ec';
	const OPENSSL_CURVE_PRIME256V1 = 'prime256v1';
	const OPENSSL_CURVE_SECP384R1 = 'secp384r1';
	const OPENSSL_CURVE_SECP521R1 = 'secp521r1';
	const OPENSSL_CURVE_SECP256K1 = 'secp256k1';

	const SEC_COMPRESS_NONE = '04';
	const SEC_COMPRESS_ODD = '03';
	const SEC_COMPRESS_EVEN = '02';

	const PEM_TYPE = 'EC';
	const PEM_COMPRESS_NONE = "\04";
	const PEM_COMPRESS_ODD = "\03";
	const PEM_COMPRESS_EVEN = "\02";
	const PEM_KEY_P_256_PUBLIC = '3059 3013 0607 2a8648ce3d0201 0608 2a8648ce3d030107 0342 00';
	const PEM_KEY_P_256_PRIVATE = '3077 020101 0420 $ a00a 0608 2a8648ce3d030107 a144 0342 00';
	const PEM_KEY_P_384_PUBLIC = '3076 3010 0607 2a8648ce3d0201 0605 2b81040022 0362 00';
	const PEM_KEY_P_384_PRIVATE = '3081a4 020101 0430 $ a007 0605 2b81040022 a164 0362 00';
	const PEM_KEY_P_521_PUBLIC = '30819b 3010 0607 2a8648ce3d0201 0605 2b81040023 038186 00';
	const PEM_KEY_P_521_PRIVATE = '3081dc 020101 0442 $ a007 0605 2b81040023 a18189 038186 00';

	const ASN1_SEQUENCE = '30';
	const ASN1_LENGTH_2BYTES = '81';
	const ASN1_INTEGER = '02';
	const ASN1_INTEGER_NEGATIVE = '00';
	const ASN1_INTEGER_BIG_LIMIT = '7f';

	/**
	 * @var string[] $_curves
	 */
	private static $_curves = array(
		self::OPENSSL_CURVE_PRIME256V1,
		self::OPENSSL_CURVE_SECP384R1,
		self::OPENSSL_CURVE_SECP521R1,
		self::OPENSSL_CURVE_SECP256K1
	);

	/**
	 * @var int[] $_curveSizeKeys
	 */
	private static $_curveSizeKeys = array(
		self::OPENSSL_CURVE_PRIME256V1 => 256,
		self::OPENSSL_CURVE_SECP384R1 => 384,
		self::OPENSSL_CURVE_SECP521R1 => 521,
		self::OPENSSL_CURVE_SECP256K1 => 256
	);

	/**
	 * @var int[] $_curveSizeComponents
	 */
	private static $_curveSizeComponents = array(
		self::OPENSSL_CURVE_PRIME256V1 => 64,
		self::OPENSSL_CURVE_SECP384R1 => 96,
		self::OPENSSL_CURVE_SECP521R1 => 132
	);

	/**
	 * @var string[] $_curvePrefixesPublic
	 */
	private static $_curvePrefixesPublic = array(
		self::OPENSSL_CURVE_PRIME256V1 => self::PEM_KEY_P_256_PUBLIC,
		self::OPENSSL_CURVE_SECP384R1 => self::PEM_KEY_P_384_PUBLIC,
		self::OPENSSL_CURVE_SECP521R1 => self::PEM_KEY_P_521_PUBLIC
	);

	/**
	 * @var string[] $_curvePrefixesPrivate
	 */
	private static $_curvePrefixesPrivate = array(
		self::OPENSSL_CURVE_PRIME256V1 => self::PEM_KEY_P_256_PRIVATE,
		self::OPENSSL_CURVE_SECP384R1 => self::PEM_KEY_P_384_PRIVATE,
		self::OPENSSL_CURVE_SECP521R1 => self::PEM_KEY_P_521_PRIVATE
	);

	/**
	 * @var int[] $_curveSignatures
	 */
	private static $_curveSignatures = array(
		self::OPENSSL_CURVE_PRIME256V1 => OPENSSL_ALGO_SHA256,
		self::OPENSSL_CURVE_SECP384R1 => OPENSSL_ALGO_SHA384,
		self::OPENSSL_CURVE_SECP521R1 => OPENSSL_ALGO_SHA512
	);

	/**
	 * @return string[]
	 */
	public static function CurveList () {
		return array(
			self::OPENSSL_CURVE_PRIME256V1,
			self::OPENSSL_CURVE_SECP384R1,
			self::OPENSSL_CURVE_SECP521R1
		);
	}

	/**
	 * @param string $content = ''
	 *
	 * @return string
	 */
	public static function CurveRecognizePublic ($content = '') {
		$buffer = null;
		$out = null;

		foreach (self::$_curvePrefixesPublic as $curve => &$prefix) {
			$buffer = self::CurvePrefixPublic($curve);

			if (substr($content, 0, strlen($buffer)) == $buffer) $out = $curve;
		}

		unset($curve, $prefix, $buffer);

		return $out;
	}

	/**
	 * @param string $content = ''
	 * @param string $modulus = ''
	 *
	 * @return string
	 */
	public static function CurveRecognizePrivate ($content = '', $modulus = '') {
		$buffer = null;
		$out = null;

		foreach (self::$_curvePrefixesPublic as $curve => &$prefix) {
			$buffer = self::CurvePrefixPublic($curve, $modulus);

			if (substr($content, 0, strlen($buffer)) == $buffer) $out = $curve;
		}

		unset($curve, $prefix, $buffer);

		return $out;
	}

	/**
	 * @param QuarkEncryptionKey $key
	 *
	 * @return bool
	 */
	public function EncryptionAlgorithmKeyGenerate (QuarkEncryptionKey &$key) {
		$details = $key->Details();

		$keyOpenSSL = openssl_pkey_new(array(
			'curve_name' => $details->Curve(),
			'private_key_type' => OPENSSL_KEYTYPE_EC
		));

		if ($keyOpenSSL === false) {
			Quark::Log('[EncryptionAlgorithmEC::KeyGenerate] OpenSSL error: "' . openssl_error_string() . '"', Quark::LOG_WARN);

			return null;
		}

		$result = openssl_pkey_get_private($keyOpenSSL);
		if ($result === false) return null;

		$detailsOpenSSL = openssl_pkey_get_details($result);
		if ($detailsOpenSSL === false) return null;

		$details->PopulateOpenSSL(self::OPENSSL_TYPE, $detailsOpenSSL);

		$key->Details($details);
		$key->ValueAsymmetricPrivate($details->ExponentPrivate());
		$key->ValueAsymmetricPublic(self::PEMExtractPublic($details->OpenSSLPublicBinary(), $details->Curve()));

		return true;
	}

	/**
	 * @param QuarkEncryptionKey $keyPrivate
	 * @param QuarkEncryptionKey $keyPublic
	 *
	 * @return string
	 */
	public function EncryptionAlgorithmKeySharedSecret (QuarkEncryptionKey &$keyPrivate, QuarkEncryptionKey &$keyPublic) {
		// TODO: Implement EncryptionAlgorithmKeySharedSecret() method.
	}

	/**
	 * @param QuarkEncryptionKey $key
	 *
	 * @return QuarkPEMDTO[]
	 */
	public function EncryptionAlgorithmKeyPEMEncode (QuarkEncryptionKey &$key) {
		$out = array();

		if ($key->IsAsymmetricPublic())
			$out[] = self::_pemPublic($key);

		if ($key->IsAsymmetricPrivate())
			$out[] = self::_pemPrivate($key);

		if ($key->IsAsymmetricPair()) {
			$out[] = self::_pemPrivate($key);
			$out[] = self::_pemPublic($key);
		}

		return $out;
	}

	/**
	 * @param QuarkEncryptionKey $key
	 * @param string $kind = ''
	 * @param string $prefix = ''
	 *
	 * @return QuarkPEMDTO
	 */
	private static function _pem (QuarkEncryptionKey &$key, $kind = '', $prefix = '') {
		$out = new QuarkPEMDTO();

		$out->Kind($kind);
		$out->Content(self::SECEncode($key, $prefix));

		return $out;
	}

	/**
	 * @param QuarkEncryptionKey $key
	 *
	 * @return QuarkPEMDTO
	 */
	private static function _pemPublic (QuarkEncryptionKey &$key) {
		return self::_pem($key, QuarkPEMIOProcessor::KIND_KEY_PUBLIC, self::CurvePrefixPublic($key->Details()->Curve()));
	}

	/**
	 * @param QuarkEncryptionKey $key
	 *
	 * @return QuarkPEMDTO
	 */
	private static function _pemPrivate (QuarkEncryptionKey &$key) {
		return self::_pem($key, self::PEM_TYPE . ' ' . QuarkPEMIOProcessor::KIND_KEY_PRIVATE, self::CurvePrefixPrivate($key->Details()->Curve(), $key->Details()->ExponentPrivate()));
	}

	/**
	 * @param QuarkEncryptionKey $key
	 * @param QuarkPEMDTO $dto
	 *
	 * @return bool
	 */
	public function EncryptionAlgorithmKeyPEMDecode (QuarkEncryptionKey &$key, QuarkPEMDTO $dto) {
		$content = $dto->Content();
		$details = new QuarkEncryptionKeyDetails();

		$buffer = null;
		$keys = null;
		$keysLen = 0;
		$prefix = null;
		$prefixLen = 0;
		$recognized = false;

		foreach (self::$_curves as $i => &$curve) {
			$recognized = false;

			if (isset(self::$_curvePrefixesPublic[$curve])) {
				$buffer = self::CurvePrefixPublic($curve);
				$prefixLen = strlen($buffer);

				if ($buffer != null) {
					$prefix = substr($content, 0, $prefixLen);

					if ($prefix == $buffer) {
						$recognized = true;

						$keys = substr($content, $prefixLen + 1);
						$keysLen = strlen($keys) / 2;

						$details->CurveCoordinateX(substr($keys, 0, $keysLen));
						$details->CurveCoordinateY(substr($keys, $keysLen));
					}
				}
			}

			if (isset(self::$_curvePrefixesPrivate[$curve])) {
				$buffer = explode('$', str_replace(' ', '', self::$_curvePrefixesPrivate[$curve]));
				$data = bin2hex($content);
				$prefix = substr($data, 0, strlen($buffer[0]));

				if ($buffer[0] == $prefix) {
					$secretSize = self::CurveSizeComponent($curve);
					$secret = substr($data, strlen($prefix), $secretSize);
					$postfix = substr($data, strlen($prefix) + $secretSize, strlen($buffer[1]));

					if ($postfix == $buffer[1]) {
						$recognized = true;

						$details->ExponentPrivate(hex2bin($secret));
					}
				}
			}

			if ($recognized) {
				$details->Curve($curve);
				$details->Bits(self::CurveSizeKey($curve));
			}
		}

		unset($buffer, $keys, $keysLen, $prefix, $prefixLen, $recognized);

		if ($dto->Kind() == QuarkPEMIOProcessor::KIND_KEY_PUBLIC)
			$key->ValueAsymmetricPublic($content);

		if ($dto->Kind() == self::PEM_TYPE . ' ' . QuarkPEMIOProcessor::KIND_KEY_PRIVATE)
			$key->ValueAsymmetricPrivate($content);

		$key->Details($details);

		return true;
	}

	/**
	 * @param QuarkEncryptionKey $key
	 * @param string $data
	 *
	 * @return string
	 */
	public function EncryptionAlgorithmSign (QuarkEncryptionKey &$key, $data) {
		$keyPrivate = null;
		$keyPEM = $key->EncryptionPrimitivePEMEncode();

		foreach ($keyPEM as $i => &$item)
			if ($item->KindIs(QuarkPEMIOProcessor::KIND_KEY_PRIVATE))
				$keyPrivate = $item;

		unset($i, $item);
		if ($keyPrivate == null) return null;

		$algorithm = $this->CurveSignature($key->Details()->Curve());
		if ($algorithm == null || !openssl_sign($data, $signature, $keyPrivate->PEMEncode(), $algorithm)) return null;

		$length = self::CurveSizeComponent($key->Details()->Curve());
		return self::fromAsn1($signature, $length);
		/*$signature = bin2hex($signature);

		if (substr($signature, 0, 2) != self::ASN1_SEQUENCE) return null;

		$offset = 2;
		if (substr($signature, 2, 2) == self::ASN1_LENGTH_2BYTES) $offset += 2;

		$pointR = self::ASN1Integer($data, $offset);
		$offset += strlen($pointR);
		$pointR = self::ASN1IntegerPositive($pointR);

		$pointS = self::ASN1Integer($data, $offset);
		$offset += strlen($pointS);
		$pointS = self::ASN1IntegerPositive($pointS);

		$length = self::CurveSizeComponent($key->Details()->Curve());
		Quark::Trace($length);

		return $length == null ? null : hex2bin(''
			 . str_pad($pointR, $length, '0', STR_PAD_LEFT)
			 . str_pad($pointS, $length, '0', STR_PAD_LEFT)
		);*/
	}

	/**
	 * @throws \Exception if the signature is not an ASN.1 sequence
	 */
	public static function fromAsn1 ($signature, $length) {
		$message = bin2hex($signature);
		$position = 0;

		if (self::ASN1_SEQUENCE !== self::readAsn1Content($message, $position, 2)) {
			throw new \Exception('Invalid data. Should start with a sequence.');
		}

		if (self::ASN1_LENGTH_2BYTES === self::readAsn1Content($message, $position, 2)) {
			$position += 2;
		}

		$pointR = self::retrievePositiveInteger(self::readAsn1Integer($message, $position));
		$pointS = self::retrievePositiveInteger(self::readAsn1Integer($message, $position));

		$bin = hex2bin(''
			 . str_pad($pointR, $length, '0', STR_PAD_LEFT)
			 . str_pad($pointS, $length, '0', STR_PAD_LEFT)
		);

		if (!is_string($bin)) {
			throw new \Exception('Unable to parse the data');
		}

		return $bin;
	}

	private static function readAsn1Content ($message, &$position, $length) {
		$content = mb_substr($message, $position, $length, '8bit');
		$position += $length;

		return $content;
	}

	/**
	 * @throws \Exception if the data is not an integer
	 */
	private static function readAsn1Integer ($message, &$position) {
		if (self::ASN1_INTEGER !== self::readAsn1Content($message, $position, 2)) {
			throw new \Exception('Invalid data. Should contain an integer.');
		}

		$length = (int) hexdec(self::readAsn1Content($message, $position, 2));

		return self::readAsn1Content($message, $position, $length * 2);
	}

	private static function retrievePositiveInteger ($data) {
		while (0 === mb_strpos($data, self::ASN1_INTEGER_NEGATIVE, 0, '8bit')
			&& mb_substr($data, 2, 2, '8bit') > self::ASN1_INTEGER_BIG_LIMIT) {
			$data = mb_substr($data, 2, null, '8bit');
		}

		return $data;
	}

	/**
	 * @param string $value = ''
	 * @param string $curve = ''
	 *
	 * @return string
	 */
	public static function SECPad ($value = '', $curve = '') {
		$size = self::CurveSizeComponent($curve);

		return $size === null ? null : str_pad($value, $size, '0', STR_PAD_LEFT);
	}

	/**
	 * @param QuarkEncryptionKey $key = null
	 * @param string $prefix = ''
	 * @param bool $compress = false
	 *
	 * @return string
	 */
	public static function SECEncode (QuarkEncryptionKey &$key = null, $prefix = '', $compress = false) {
		if ($key == null) return null;

		$details = $key->Details();
		if ($details == null) return null;

		$x = QuarkMathNumber::InitHexadecimal(bin2hex($details->CurveCoordinateX()));
		$y = QuarkMathNumber::InitHexadecimal(bin2hex($details->CurveCoordinateY()));
		$xOut = self::SECPad($x->Stringify(), $details->Curve());
		$yOut = self::SECPad($y->Stringify(), $details->Curve());

		$marker = self::PEM_COMPRESS_NONE;
		$trail = '';

		if ($compress) $marker = $y->Even() ? self::PEM_COMPRESS_EVEN : self::PEM_COMPRESS_ODD;
		else $trail = hex2bin($yOut);

		return $prefix . $marker . hex2bin($xOut) . $trail;
	}

	/**
	 * @param string $data = ''
	 * @param string $curve = ''
	 *
	 * @return QuarkEncryptionKey
	 */
	public static function SECDecode ($data = '', $curve = '') {
		$size = self::CurveSizeKey($curve);
		if ($size == null) return null;

		$out = null;

		if (strlen($data) == ceil($size / 8)) {
			$out = self::KeyInit($curve);
			$out->ValueAsymmetricPrivate($data);
			$out->Details()->ExponentPrivate($data);
		}
		else {
			$dataBin = bin2hex($data);
			$compress = substr($dataBin, 0, 2);

			if ($compress === self::SEC_COMPRESS_NONE) {
				$out = self::KeyInit($curve);
				$out->ValueAsymmetricPublic($data);

				$keys = substr($dataBin, 2);
				$keysLen = strlen($keys) / 2;

				$out->Details()->CurveCoordinateX(hex2bin(substr($keys, 0, $keysLen)));
				$out->Details()->CurveCoordinateY(hex2bin(substr($keys, $keysLen)));

				unset($keys, $keysLen);
			}

			unset($dataBin, $compress);
		}

		return $out;
	}

	/**
	 * @param string $curve = ''
	 *
	 * @return QuarkEncryptionKey
	 */
	public static function KeyInit ($curve = '') {
		$size = self::CurveSizeKey($curve);
		if ($size === null) return null;

		$out = new QuarkEncryptionKey();
		$out->Algorithm(new self());

		$details = new QuarkEncryptionKeyDetails();
		$details->Curve($curve);
		$details->Bits($size);

		$out->Details($details);

		return $out;
	}

	/**
	 * @param string $curve = self::OPENSSL_CURVE_PRIME256V1
	 *
	 * @return QuarkEncryptionKey
	 */
	public static function KeyGenerate ($curve = self::OPENSSL_CURVE_PRIME256V1) {
		$key = new QuarkEncryptionKey();

		$key->Algorithm(new self());
		$key->Details(QuarkEncryptionKeyDetails::FromCurve($curve));

		return $key->Generate() ? $key : null;
	}

	/**
	 * @param string $curve = ''
	 *
	 * @return int
	 */
	public static function CurveSizeKey ($curve = '') {
		return isset(self::$_curveSizeKeys[$curve]) ? self::$_curveSizeKeys[$curve] : null;
	}

	/**
	 * @param string $curve = ''
	 *
	 * @return int
	 */
	public static function CurveSizeComponent ($curve = '') {
		return isset(self::$_curveSizeComponents[$curve]) ? self::$_curveSizeComponents[$curve] : null;
	}

	/**
	 * @param string $curve = ''
	 *
	 * @return int
	 */
	public static function CurvePrefixPublic ($curve = '') {
		return isset(self::$_curvePrefixesPublic[$curve]) ? pack('H*', str_replace(' ', '', self::$_curvePrefixesPublic[$curve])) : null;
	}

	/**
	 * @param string $curve = ''
	 * @param string $modulus = null
	 *
	 * @return int
	 */
	public static function CurvePrefixPrivate ($curve = '', $modulus = null) {
		if (!isset(self::$_curvePrefixesPrivate[$curve])) return null;

		$prefix = self::$_curvePrefixesPrivate[$curve];

		if (func_num_args() > 1)
			$prefix = str_replace('$', unpack('H*', self::PEMPadBinary($modulus))[1], $prefix);

		return pack('H*', str_replace(' ', '', $prefix));
	}

	/**
	 * @param string $curve = ''
	 *
	 * @return int
	 */
	public static function CurveSignature ($curve = '') {
		return isset(self::$_curveSignatures[$curve]) ? self::$_curveSignatures[$curve] : null;
	}

	/**
	 * @param string $value = ''
	 * @param string $curve = ''
	 *
	 * @return string
	 */
	public static function PEMExtractPublic ($value = '', $curve = '') {
		$prefix = self::CurvePrefixPublic($curve);

		return $prefix == null ? null : substr($value, strlen($prefix));
	}

	/**
	 * @param string $value = ''
	 * @param string $curve = ''
	 * @param string $modulus = ''
	 *
	 * @return string
	 */
	public static function PEMExtractPrivate ($value = '', $curve = '', $modulus = '') {
		$prefix = self::CurvePrefixPrivate($curve, $modulus);

		return $prefix == null ? null : substr($value, strlen($prefix));
	}

	/**
	 * openssl ec -inform PEM -check -text -in ./test521.pem
	 *
	 * @param string $value = ''
	 * @param string $curve = ''
	 *
	 * @return string
	 */
	public static function PEMPadBinary ($value = '', $curve = '') {
		// P-521
		// reference was ceiling, but temporarily worked with floor, need testing
		return str_pad($value, floor(self::CurveSizeKey($curve) / 8), "\0", STR_PAD_LEFT);
	}

	public static function ASN1Integer ($data = '', $offset = 0) {
		if (substr($data, $offset, 2) != self::ASN1_INTEGER) return null;

		$length = (int)hexdec(substr($data, $offset + 2, 2));

		return substr($data, $offset + 4, $length * 2);
	}

	/**
	 * @param string $data = ''
	 *
	 * @return string
	 */
	public static function ASN1IntegerPositive ($data = '') {
		$flag = true;

		while ($flag) {
			$flag = true
				&& strpos($data, self::ASN1_INTEGER_NEGATIVE, 0) === 0
				&& substr($data, 2, 2) > self::ASN1_INTEGER_BIG_LIMIT;

			$data = substr($data, 2, null);
		}

		return $data;
	}
}