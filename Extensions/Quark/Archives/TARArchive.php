<?php
namespace Quark\Extensions\Quark\Archives;

use Quark\IQuarkArchive;
use Quark\IQuarkCompressor;

use Quark\QuarkArchiveItem;
use Quark\QuarkDate;

/**
 * Class TARArchive
 *
 * https://habrahabr.ru/post/207470/
 * http://www.mkssoftware.com/docs/man4/tar.4.asp
 * https://stackoverflow.com/a/31118634/2097055
 *
 * @package Quark\Extensions\Quark\Archives
 */
class TARArchive implements IQuarkArchive {
	const BLOCK = 512;
	
	const FLAG_DIR = 5;
	const FLAG_FILE = 0;
	
	const MAGIC = 'ustar';
	
	const CHECKSUM_START = 148;
	const CHECKSUM_LENGTH = 8;
	
	/**
	 * @var IQuarkCompressor $_compressor
	 */
	private $_compressor;
	
	/**
	 * @param IQuarkCompressor $compressor = null
	 */
	public function __construct (IQuarkCompressor $compressor = null) {
		$this->Compressor($compressor);
	}
	
	/**
	 * @param IQuarkCompressor $compressor = null
	 *
	 * @return IQuarkCompressor
	 */
	public function Compressor (IQuarkCompressor $compressor = null) {
		if (func_num_args() != 0)
			$this->_compressor = $compressor;
		
		return $this->_compressor;
	}
	
	/**
	 * @param QuarkArchiveItem $item
	 *
	 * @return float|int
	 */
	public function Block (QuarkArchiveItem $item) {
		return $item->size == 0 ? 0 : ceil(($item->size < self::BLOCK ? self::BLOCK : $item->size) / self::BLOCK) * self::BLOCK;
	}
	
	/**
	 * @param QuarkArchiveItem[] $items
	 *
	 * @return string
	 */
	public function Pack ($items) {
		$out = '';
		
		foreach ($items as $item)
			$out .= $this->PackItem($item);
		
		return $this->_compressor ? $this->Compressor()->Compress($out) : $out;
	}
	
	/**
	 * @param QuarkArchiveItem $item
	 * @param int $user = 0
	 * @param int $group = 0
	 *
	 * @return string
	 */
	public function PackItem (QuarkArchiveItem $item, $user = 0, $group = 0) {
		$perms = sprintf('%6s ', decoct($item->Permissions()));
		$time = sprintf('%11s ', decoct($item->DateModified()->Timestamp()));
		$size = sprintf('%11u ', decoct($item->size));
		$magic = sprintf('%5s ', self::MAGIC);
		$flag = $item->isDir ? self::FLAG_DIR : self::FLAG_FILE;
		$link = '';
		
		$user_id = sprintf('%6s ', decoct($user));
		$user_name = '';
		
		$group_id = sprintf('%6s ', decoct($group));
		$group_name = '';
		
		$device_major = '';
		$device_minor = '';
		
		$version = '';
		$prefix = '';
		$other = '';
		
		$first = pack('a100a8a8a8a12a12', $item->location, $perms, $user_id, $group_id, $size, $time);
		$last = pack('a1a100a6a2a32a32a8a8a155a12', $flag, $link, $magic, $version, $user_name, $group_name, $device_major, $device_minor, $prefix, $other);
		
		$checksum = 0;
		
		$i = 0;
		$j = 0;
		
		while ($i < self::CHECKSUM_START) {
			$checksum += ord(substr($first, $i, 1));
			$i++;
		}
		
		while ($i < self::CHECKSUM_START + self::CHECKSUM_LENGTH) {
			$checksum += ord(' ');
			$i++;
		}
		
		while ($i < self::BLOCK) {
			$checksum += ord(substr($last, $j,1));
			$i++;$j++;
		}
		
		$header = $first . pack('a8', sprintf('%6s ', decoct($checksum))) . $last;
		
		return $header . str_pad($item->Content(), $this->Block($item), ' ');
	}
	
	/**
	 * @param string $content
	 *
	 * @return QuarkArchiveItem[]
	 */
	public function Unpack ($content = '') {
		if ($this->_compressor)
			$content = $this->_compressor->Decompress($content);
		
		$next = 0;
		$read = true;
		$orig = strlen(trim($content));
		$out = array();
		
		while ($read) {
			$item = $this->UnpackItem($content, $next);
			$next = $item->Next();
			$read = $item->Next() < $orig;
			$out[] = $item;
		}
		
		return $out;
	}
	
	/**
	 * @param string $content
	 * @param int $start
	 *
	 * @return QuarkArchiveItem
	 */
	public function UnpackItem ($content = '', $start = 0) {
		$header = substr($content, $start, self::BLOCK);
		$meta = unpack('a100name/a8perms/a8uid/a8gid/a12size/a12time/a8checksum/a1flag/a100link/a6magic/a2version/a32user_name/a32group_name/a8device_major/a8device_minor/a155prefix/a12other', $header);
		
		$item = new QuarkArchiveItem(
			trim($meta['name']),
			'',
			QuarkDate::FromTimestamp(octdec($meta['time'])),
			octdec($meta['size']),
			$meta['flag'] == self::FLAG_DIR
		);
		
		$item->Content(substr($content, $start + self::BLOCK, $item->size));
		$item->Next($start + self::BLOCK + $this->Block($item));
		
		return $item;
	}
}