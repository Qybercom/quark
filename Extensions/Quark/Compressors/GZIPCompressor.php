<?php
namespace Quark\Extensions\Quark\Compressors;

use Quark\IQuarkCompressor;

use Quark\QuarkArchException;

/**
 * Class GZIPCompressor
 *
 * @package Quark\Extensions\Quark\Compressors
 */
class GZIPCompressor implements IQuarkCompressor {
	/**
	 * @param string $data
	 *
	 * @return string
	 *
	 * @throws QuarkArchException
	 */
	public function Compress ($data) {
		if (!function_exists('gzencode'))
			throw new QuarkArchException('[GZIPCompressor::Compress] Function "gzencode" not found. Please check that "zlib" extension is configured for your PHP installation.');
		
		return gzencode($data);
	}
	
	/**
	 * @param string $data
	 *
	 * @return string
	 *
	 * @throws QuarkArchException
	 */
	public function Decompress ($data) {
		if (!function_exists('gzdecode'))
			throw new QuarkArchException('[GZIPCompressor::Uncompress] Function "gzdecode" not found. Please check that "zlib" extension is configured for your PHP installation.');
		
		return gzdecode($data);
	}
}