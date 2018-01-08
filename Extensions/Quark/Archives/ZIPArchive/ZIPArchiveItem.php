<?php
namespace Quark\Extensions\Quark\Archives\ZIPArchive;

use Quark\Quark;
use Quark\QuarkArchiveItem;
use Quark\QuarkDate;
use Quark\QuarkObject;

/**
 * Class ZIPArchiveItem
 *
 * @package Quark\Extensions\Quark\Archives
 */
class ZIPArchiveItem {
	/**
	 * @var ZIPArchiveHeaderCentralDirectory $_headerCentralDirectory
	 */
	private $_headerCentralDirectory;

	/**
	 * @var ZIPArchiveHeaderLocal $_headerLocal
	 */
	private $_headerLocal;

	/**
	 * @var string $_content = ''
	 */
	private $_content = '';

	/**
	 * @return ZIPArchiveHeaderCentralDirectory
	 */
	public function &HeaderCentralDirectory () {
		return $this->_headerCentralDirectory;
	}

	/**
	 * @return ZIPArchiveHeaderLocal
	 */
	public function &HeaderLocal () {
		return $this->_headerLocal;
	}

	/**
	 * @return string
	 */
	public function &Content () {
		return $this->_content;
	}

	/**
	 * @return QuarkArchiveItem
	 */
	public function QuarkArchive () {
		Quark::Requires('zlib', 'gzinflate');

		return new QuarkArchiveItem(
			$this->_headerLocal->name,
			$this->_headerLocal->size_normal == $this->_headerLocal->size_compressed ? $this->_content : gzinflate($this->_content),
			QuarkDate::FromMSDOSDate($this->_headerLocal->date, $this->_headerLocal->time),
			$this->_headerLocal->size_normal,
			substr($this->_headerLocal->name, -1) == '/'
		);
	}

	/**
	 * @param ZIPArchiveHeaderCentralDirectory $centralDirectory = null
	 * @param string $data = ''
	 *
	 * @return ZIPArchiveItem
	 */
	public static function FromCentralDirectory (ZIPArchiveHeaderCentralDirectory $centralDirectory = null, $data = '') {
		if ($centralDirectory == null) return null;

		/**
		 * @var ZIPArchiveHeaderLocal $local
		 */
		$local = QuarkObject::BinaryPopulate(
			new ZIPArchiveHeaderLocal(),
			substr($data, $centralDirectory->local_header_offset)
		);

		$content = substr($data, $centralDirectory->local_header_offset + $local->BinaryLength(), $local->size_compressed);

		$out = new self();
		$out->_headerCentralDirectory = $centralDirectory;
		$out->_headerLocal = $local;
		$out->_content = $content;

		return $out;
	}

	/**
	 * @param QuarkArchiveItem $item = null
	 * @param int $level = 0
	 *
	 * @return ZIPArchiveItem
	 */
	public static function FromQuarkArchiveItem (QuarkArchiveItem $item = null, $level = 0) {
		Quark::Requires('zlib', 'gzdeflate');

		if ($item == null) return null;

		$date = $item->DateModified()->ToMSDOSDate();

		$out = new self();
		$out->_content = $level == 0 ? $item->Content() : gzdeflate($item->Content(), $level);

		$centralDirectory = new ZIPArchiveHeaderCentralDirectory();
		$centralDirectory->signature = ZIPArchive::SIGNATURE_CDFH;
		$centralDirectory->name = $item->Location();
		$centralDirectory->checksum = crc32($item->Content());
		$centralDirectory->level = $level;
		$centralDirectory->size_compressed = strlen($out->_content);
		$centralDirectory->size_normal = strlen($item->Content());
		$centralDirectory->version_extract = ZIPArchive::VERSION;
		$centralDirectory->version_origin = ZIPArchive::VERSION;
		$centralDirectory->length_name = strlen($centralDirectory->name);
		$centralDirectory->date = $date->Date;
		$centralDirectory->time = $date->Time;

		$local = new ZIPArchiveHeaderLocal();
		$local->signature = ZIPArchive::SIGNATURE_LFH;
		$local->name = $item->Location();
		$local->checksum = crc32($item->Content());
		$local->level = $level;
		$local->size_compressed = strlen($out->_content);
		$local->size_normal = strlen($item->Content());
		$local->version = ZIPArchive::VERSION;
		$local->length_name = strlen($local->name);
		$local->length_extra = strlen($local->extra);
		$local->date = $date->Date;
		$local->time = $date->Time;

		$out->_headerCentralDirectory = $centralDirectory;
		$out->_headerLocal = $local;

		return $out;
	}
}