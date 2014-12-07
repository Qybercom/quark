<?php
namespace Quark\Tools;

use Quark\QuarkArchException;

/**
 * Class QuarkSource
 *
 * @package Quark\Tools
 */
class QuarkSource {
	private $_source = '';

	private $_trim = array(
		'.',',',';','\'','?',':',
		'(',')','{','}','[',']',
		'-','+','*','/',
		'>','<','>=','<=','!=','==',
		'=','=>','->'
	);

	/**
	 * @param string $source
	 */
	public function __construct ($source = '') {
		$this->Load($source);
	}

	/**
	 * @param $file
	 *
	 * @return QuarkSource
	 * @throws QuarkArchException
	 */
	public static function FromFile ($file) {
		$source = new self();

		if (!$source->Load($file))
			throw new QuarkArchException('There is no source file at ' . (string)$file);

		return $source;
	}

	/**
	 * @param $source
	 *
	 * @return bool
	 */
	public function Load ($source) {
		if (!is_file($source)) return false;

		$this->_source = file_get_contents($source);
		return true;
	}

	/**
	 * @param $destination
	 *
	 * @return bool
	 */
	public function Save ($destination) {
		if (!is_file($destination)) return false;

		file_put_contents($destination, $this->_source);
		return true;
	}

	/**
	 * @return string
	 */
	public function Source () {
		return $this->_source;
	}

	/**
	 * @param string $dim
	 * @param int    $precision
	 *
	 * @return float|int
	 */
	public function Size ($dim = 'k', $precision = 3) {
		$size = strlen($this->_source);

		switch ($dim) {
			default: break;
			case 'b': break;
			case 'k': $size = $size / 1024; break;
			case 'm': $size = $size / 1024 / 1024; break;
			case 'g': $size = $size / 1024 / 1024 / 1024; break;
			case 't': $size = $size / 1024 / 1024 / 1024 / 1024; break;
		}

		if ($dim != 'b')
			$size = round($size, $precision);

		return $size;
	}

	/**
	 * @param bool $css
	 *
	 * @return $this
	 */
	public function Obfuscate ($css = false) {
		$this->_source = preg_replace('#\/\*(.*)\*\/#Uis', '', $this->_source);
		$this->_source = str_replace("\r\n", '', $this->_source);
		$this->_source = preg_replace('/\s+/', ' ', $this->_source);
		$this->_source = trim(str_replace('<?phpn', '<?php n', $this->_source));

		foreach ($this->_trim as $i => $rule) {
			$this->_source = str_replace(' ' . $rule . ' ', $rule, $this->_source);

			if (!$css)
				$this->_source = str_replace(' ' . $rule, $rule, $this->_source);

			$this->_source = str_replace($rule . ' ', $rule, $this->_source);
		}

		return $this;
	}
}