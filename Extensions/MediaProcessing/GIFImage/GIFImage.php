<?php
namespace Quark\Extensions\MediaProcessing\GIFImage;

/**
 * Class GIFImage
 *
 * Origin from https://github.com/Sybio/GifCreator
 *
 * @package Quark\Extensions\MediaProcessing\GIFImage
 */
class GIFImage {
	const GIF_87 = 'GIF87a';
	const GIF_89 = 'GIF89a';
	
	const COLOR_TRANSPARENT = -1;
	const COLOR_BLACK = 0;
	
	const DISPLAY_DEFAULT = 0;
	const DISPLAY_OVERLAY = 1;
	const DISPLAY_REWRITE = 2;
	const DISPLAY_REWIND = 4;
	
	const LOOP_INFINITE = 0;
	
	/**
	 * @var int $_loop = self::LOOP_INFINITE
	 */
	private $_loop = self::LOOP_INFINITE;
    
    /**
     * @var int $_color = self::COLOR_BLACK
     */
	private $_color = self::COLOR_BLACK;
	
	/**
	 * @var int $_display = self::DISPLAY_REWRITE
	 */
	private $_display = self::DISPLAY_REWRITE;
	
	/**
	 * @var string $_img = ''
	 */
	private $_img = '';
	
	/**
	 * @var GIFFrame[] $_frames = []
	 */
	private $_frames = [];
	
	/**
	 * @var GIFFrame $_first = null
	 */
	private $_first = null;
	
	/**
	 * @param int $loop = self::LOOP_INFINITE
	 * @param int $color = self::COLOR_BLACK
	 * @param int $display = self::DISPLAY_REWRITE
	 */
	public function __construct ($loop = self::LOOP_INFINITE, $color = self::COLOR_BLACK, $display = self::DISPLAY_REWRITE) {
		$this->Loop($loop);
		$this->Color($color);
		$this->Display($display);
	}
	
	/**
	 * @param int $loop = self::LOOP_INFINITE
	 *
	 * @return int
	 */
	public function Loop ($loop = self::LOOP_INFINITE) {
		if (func_num_args() != 0)
			$this->_loop = $loop;
		
		return $this->_loop;
	}
	
	/**
	 * @param int $color = self::COLOR_BLACK
	 *
	 * @return int
	 */
	public function Color ($color = self::COLOR_BLACK) {
		if (func_num_args() != 0)
			$this->_color = $color;
		
		return $this->_color;
	}
	
	/**
	 * @param int $display = self::DISPLAY_REWRITE
	 *
	 * @return int
	 */
	public function Display ($display = self::DISPLAY_REWRITE) {
		if (func_num_args() != 0)
			$this->_display = $display;
		
		return $this->_display;
	}
	
	/**
	 * @param GIFFrame $frame = null
	 *
	 * @return GIFImage
	 */
	public function Frame (GIFFrame $frame = null) {
		if ($frame != null) {
			if ($this->_first == null)
				$this->_first = $frame;
			
			$this->_frames[] = $frame;
		}
		
		return $this;
	}
    
    /**
     * @return string
	 */
	public function Image () {
		if ($this->_img == '') {
			$this->_img = self::GIF_89;
			
			$first = $this->_frames[0];
			
			if ($first->Palette()) {
				$this->_img .= substr($first->Img(), 6, 7);
				$this->_img .= substr($first->Img(), 13, $first->ColorMap());
				$this->_img .= "!\377\13NETSCAPE2.0\3\1" . $this->AsciiToChar($this->_loop) . "\0";
			}
			
			foreach ($this->_frames as $i => &$frame)
				$this->_img .= $frame->Compile($this->_display);
			
			//$this->_img .= ';';
		}
		
		return $this->_img;
	}
    
	/**
     * Encode an ASCII char into a string char (old: GIFWord)
     *
     * @param int $char
     *
     * @return string
	 */
	public function ASCIIToChar ($char) {
		return (chr($char & 0xFF) . chr(($char >> 8) & 0xFF));
	}
}