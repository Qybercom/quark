<?php
namespace Quark\Extensions\LabelCode\Providers;

use Quark\Extensions\LabelCode\IQuarkLabelCodeProvider;

/**
 * Class LCProviderEAN13
 *
 * http://forum.dklab.ru/viewtopic.php?t=21222
 *
 * @package Quark\Extensions\LabelCode\Providers
 */
class LCProviderEAN13 implements IQuarkLabelCodeProvider {
	const LENGTH_ALL = 12;
	const LENGTH_CONTROL = 10;
	const LENGTH_MIDDLE = 6;
	
	const CODE_L = 'L';
	const CODE_R = 'R';
	const CODE_G = 'G';
	
	/**
	 * @var array $_codes
	 */
	private static $_codes = array(
		self::CODE_L => array('0001101','0011001','0010011','0111101','0100011','0110001','0101111','0111011','0110111','0001011'),
		self::CODE_R => array('1110010','1100110','1101100','1000010','1011100','1001110','1010000','1000100','1001000','1110100'),
		self::CODE_G => array('0100111','0110011','0011011','0100001','0011101','0111001','0000101','0010001','0001001','0010111'),
	);
	
	/**
	 * @var string[] $_masks
	 */
	private static $_masks = array('000000','001011','001101','001110','010011','011001','011100','010101','010110','011010');
	
	/**
	 * @return array
	 */
	public static function Codes () {
		return self::$_codes;
	}
	
	/**
	 * @param string $data = ''
	 *
	 * @return int
	 */
	public function Control ($data = '') {
		$i = 0;
		$odd = 0;
		$even = 0;
		
        while ($i < self::LENGTH_ALL) {
			if (($i + 1) % 2 == 0) $even += (int)$data[$i];
			else $odd += (int)$data[$i];
			
			$i++;
		}
		
        return ($even * 3 + $odd) % 10;
	}
	
	/**
	 * @return int
	 */
	public function LCProviderPointWidth () {
		return 1;
	}
	
	/**
	 * @return int
	 */
	public function LCProviderPointHeight () {
		return 25;
	}
	
	/**
	 * @param string $data
	 *
	 * @return string
	 */
	public function LCProviderEncode ($data) {
		$data = substr($data, 0, self::LENGTH_ALL);
		$data .= $this->Control($data);
		
		$i = 0;
		$out = '101';
		
		while ($i < self::LENGTH_ALL) {
			if ($i == self::LENGTH_MIDDLE) $out .= '01010';
			
			$mask = isset(self::$_masks[$data[0]][$i])
				? (self::$_masks[$data[0]][$i] == '1' ? self::CODE_G : self::CODE_L)
				: self::CODE_R;
			
			$out .= self::$_codes[$mask][(int)$data[$i + 1]];
			
			$i++;
		}
		
		return $out . '101';
	}
}