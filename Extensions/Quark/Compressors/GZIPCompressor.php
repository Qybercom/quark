<?php
namespace Quark\Extensions\Quark\Compressors;

use Quark\IQuarkCompressor;

use Quark\Quark;
use Quark\QuarkArchException;

/**
 * Class GZIPCompressor
 *
 * @package Quark\Extensions\Quark\Compressors
 */
class GZIPCompressor implements IQuarkCompressor {
	/**
	 * @var int $_mode = FORCE_GZIP
	 */
	private $_mode = FORCE_GZIP;

	/**
	 * @var int $_level = -1
	 */
	private $_level = -1;

	/**
	 * @param int $level = -1
	 * @param int $mode = FORCE_GZIP
	 */
	public function __construct ($level = -1, $mode = FORCE_GZIP) {
		$this->_level = $level;
		$this->_mode = $mode;
	}

	/**
	 * @param int $level = -1
	 *
	 * @return int
	 */
	public function Level ($level = -1) {
		if (func_num_args() != 0)
			$this->_level = $level;

		return $this->_level;
	}

	/**
	 * @param int $mode = FORCE_GZIP
	 *
	 * @return int
	 */
	public function Mode ($mode = FORCE_GZIP) {
		if (func_num_args() != 0)
			$this->_mode = $mode;

		return $this->_mode;
	}

	/**
	 * @param string $data
	 *
	 * @return string
	 *
	 * @throws QuarkArchException
	 */
	public function Compress ($data) {
		Quark::Requires('zlib', 'gzencode');

		return gzencode($data, $this->_level, $this->_mode);
	}
	
	/**
	 * @param string $data
	 *
	 * @return string
	 *
	 * @throws QuarkArchException
	 */
	public function Decompress ($data) {
		Quark::Requires('zlib', 'gzdecode');

		return gzdecode($data);
	}
}