<?php
namespace Quark\Extensions\LabelCode\Providers;

use Quark\Extensions\LabelCode\IQuarkLabelCodeProvider;

/**
 * Class LCProviderCode39
 *
 * http://lifeexample.ru/php-primeryi-skriptov/php-shtrih-kod.html
 *
 * @package Quark\Extensions\LabelCode\Providers
 */
class LCProviderCode39 implements IQuarkLabelCodeProvider {
	/**
	 * @var array $_chars
	 */
	private static $_chars = array(
		'0' => '1010001110111010',
		'1' => '1110100010101110',
		'2' => '1011100010101110',
		'3' => '1110111000101010',
		'4' => '1010001110101110',
		'5' => '1110100011101010',
		'6' => '1011100011101010',
		'7' => '1010001011101110',
		'8' => '1110100010111010',
		'9' => '1011100010111010',
		'A' => '1110101000101110',
		'B' => '1011101000101110',
		'C' => '1110111010001010',
		'D' => '1010111000101110',
		'E' => '1110101110001010',
		'F' => '1011101110001010',
		'G' => '1010100011101110',
		'H' => '1110101000111010',
		'I' => '1011101000111010',
		'J' => '1010111000111010',
		'K' => '1110101010001110',
		'L' => '1011101010001110',
		'M' => '1110111010100010',
		'N' => '1010111010001110',
		'O' => '1110101110100010',
		'P' => '1011101110100010',
		'Q' => '1010101110001110',
		'R' => '1110101011100010',
		'S' => '1011101011100010',
		'T' => '1010111011100010',
		'U' => '1110001010101110',
		'V' => '1000111010101110',
		'W' => '1110001110101010',
		'X' => '1000101110101110',
		'Y' => '1110001011101010',
		'Z' => '1000111011101010',
		'-' => '1000101011101110',
		'.' => '1110001010111010',
		' ' => '1000111010111010',
		'*' => '1000101110111010',
		'$' => '1000100010001010',
		'/' => '1000100010100010',
		'+' => '1000101000100010',
		'%' => '1010001000100010'
	);
	
	/**
	 * @var bool $_force = false
	 */
	private $_force = false;
	
	/**
	 * @param bool $force = false
	 */
	public function __construct ($force = false) {
		$this->Force($force);
	}
	
	/**
	 * @param bool $force = false
	 *
	 * @return bool
	 */
	public function Force ($force = false) {
		if (func_num_args() != 0)
			$this->_force = $force;
		
		return $this->_force;
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
		$data = '*' . $data . '*';
		$data = strtoupper($data);
		$i = 0;
		$length = strlen($data);
		$out = '';
		
		while ($i < $length) {
			if (!isset(self::$_chars[$data[$i]])) {
				if (!$this->_force) break;
				else {
					$i++;
					continue;
				}
			}
			
			$out .= self::$_chars[$data[$i]];
			
			$i++;
		}
		
		return $out;
	}
}