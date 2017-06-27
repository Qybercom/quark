<?php
namespace Quark\Extensions\Quark\Compressors;

use Quark\IQuarkCompressor;

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
		if (!function_exists('bzcompress'))
			throw new QuarkArchException('[BZIP2Compressor::Compress] Function "bzcompress" not found. Please check that "Bzip2" extension is configured for your PHP installation.');
		
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
		if (!function_exists('bzdecompress'))
			throw new QuarkArchException('[BZIP2Compressor::Uncompress] Function "bzdecompress" not found. Please check that "Bzip2" extension is configured for your PHP installation.');
		
		return bzdecompress($data);
	}
}