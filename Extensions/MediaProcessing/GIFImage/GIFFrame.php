<?php
namespace Quark\Extensions\MediaProcessing\GIFImage;

use Quark\Extensions\MediaProcessing\GraphicsDraw\GDImage;

/**
 * Class GIFFrame
 *
 * @package Quark\Extensions\MediaProcessing\GIFImage
 */
class GIFFrame {
	const RGB_RED = 0;
	const RGB_GREEN = 1;
	const RGB_BLUE = 2;
	
	/**
	 * @var string $_img = ''
	 */
	private $_img = '';
	
	/**
	 * @var int $_duration = 0
	 */
	private $_duration = 0;
	
	/**
	 * @param string $img = ''
	 * @param int $duration = 0
	 */
	public function __construct ($img = '', $duration = 0) {
		$this->Img($img);
		$this->Duration($duration);
	}
	
	/**
	 * @param string $img = ''
	 *
	 * @return string
	 */
	public function Img ($img = '') {
		if (func_num_args() != 0)
			$this->_img = $img;
		
		return $this->_img;
	}
	
	/**
	 * @param int $duration = 0
	 *
	 * @return int
	 */
	public function Duration ($duration = 0) {
		if (func_num_args() != 0)
			$this->_duration = $duration;
		
		return $this->_duration;
	}
	
	public function Width () {
		return substr($this->_img, 6, 7);
	}
	
	/**
	 * @return int
	 */
	public function LengthRaw () {
		return ord($this->_img[10]) & 0x07;
	}
	
	/**
	 * @return int
	 */
	public function Length () {
		return 2 << $this->LengthRaw();
	}
	
	/**
	 * @return int
	 */
	public function ColorMap () {
		return 3 * $this->Length();
	}
	
	/**
	 * @return string
	 */
	public function RGB () {
		return substr($this->_img, 13, $this->ColorMap());
	}
	
	/**
	 * @return string
	 */
	private function _img () {
		$start = 13 + $this->ColorMap();
		$end   = strlen($this->_img) - $start - 1;
		
		return substr($this->_img, $start, $end);
	}
	
	/**
	 * @return string
	 */
	public function Meta () {
		$out = '';
		$img = $this->_img();
		
		if ($img[0] == '!') $out = substr($img, 8, 10);
		if ($img[0] == ',') $out = substr($img, 0, 10);
		
		if ($this->Palette())
			$out[9] = chr(ord($out[9]) | 0x80 & 0xF8 | $this->LengthRaw());
		
		return $out;
	}
	
	/**
	 * @return string
	 */
	public function Payload () {
		$offset = 0;
		$img = $this->_img();
		
		if ($img[0] == '!') $offset = 18;
		if ($img[0] == ',') $offset = 10;
		
		$offset = $offset == 0 ? 1 : $offset;
		
		return substr($img, $offset, strlen($img) - $offset);
	}
	
	/**
	 * @return int
	 */
	public function Palette () {
		return ord($this->_img[10]) & 0x80;
	}
	
	/**
	 * @param int $component
	 * @param int $i = 0
	 *
	 * @return mixed
	 */
	public function RGBComponent ($component, $i = 0) {
		return $this->RGB()[3 * $i + $component];
	}
	
	/**
	 * @param string $red = ''
	 * @param string $green = ''
	 * @param string $blue = ''
	 *
	 * @return int
	 */
	public function RGBColor ($red = '', $green = '', $blue = '') {
		$i = 0;
		$length = $this->Length();
		$rgb = $this->RGB();
		
		while ($i < $length) {
			if (ord($rgb[3 * $i + 0]) != $red) { $i++; continue; }
			if (ord($rgb[3 * $i + 1]) != $green) { $i++; continue; }
			if (ord($rgb[3 * $i + 2]) != $blue) { $i++; continue; }
				
			break;
		}
		
		return $i;
	}
	
	/**
	 * @param int $display = GIFImage::DISPLAY_REWRITE
	 *
	 * @return string
	 */
	public function Compile ($display = GIFImage::DISPLAY_REWRITE) {
		$duration = $this->Duration();
		
		return "!\xF9\x04"
				. chr(($display << 2) + 1)
				. chr(($duration >> 0) & 0xFF)
				. chr(($duration >> 8) & 0xFF)
				. chr($this->RGBColor())
				. "\x0"
				. $this->Meta()
				. $this->RGB()
				. $this->Payload();
	}
	
	/**
	 * @param GDImage $image = null
	 * @param int $duration = 0
	 *
	 * @return GIFFrame
	 */
	public static function FromImage (GDImage $image = null, $duration = 0) {
		return $image == null ? null : new self($image->Convert(GDImage::TYPE_GIF)->Content(), $duration);
	}
}