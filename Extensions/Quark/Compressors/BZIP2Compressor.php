<?php
namespace Quark\Extensions\Quark\Compressors;

use Quark\IQuarkCompressor;

use Quark\Quark;
use Quark\QuarkArchException;

/**
 * Class BZIP2Compressor
 *
 * @package Quark\Extensions\Quark\Compressors
 */
class BZIP2Compressor implements IQuarkCompressor {
	/**
	 * @param string $data
	 *
	 * @return string
	 *
	 * @throws QuarkArchException
	 */
	public function Compress ($data) {
		Quark::Requires('Bzip2', 'bzcompress');

		return bzcompress($data);
	}
	
	/**
	 * @param string $data
	 *
	 * @return string
	 *
	 * @throws QuarkArchException
	 */
	public function Decompress ($data) {
		Quark::Requires('Bzip2', 'bzdecompress');

		return bzdecompress($data);
	}
}