<?php
namespace Quark\Extensions\Quark\Archives\ZIPArchive;

use Quark\Quark;
use Quark\QuarkArchiveItem;
use Quark\QuarkDate;
use Quark\QuarkObject;

/**
 * Class ZIPArchiveItem
 *
 * https://github.com/archiverjs/node-zip-stream/issues/8
 * https://adayinthelifeof.nl/2010/01/14/handling-binary-data-in-php-with-pack-and-unpack/
 *
 * https://justanapplication.wordpress.com/category/file-formats/zip-file-format/
 *
 * @package Quark\Extensions\Quark\Archives
 */
class ZIPArchiveItem {
	/**
	 * @var ZIPArchiveHeaderFile $_headerCentral
	 */
	private $_headerCentral;

	/**
	 * @var ZIPArchiveHeaderFileLocal $_headerLocal
	 */
	private $_headerLocal;

	/**
	 * @var string $_content = ''
	 */
	private $_content = '';

	/**
	 * @return ZIPArchiveHeaderFile
	 */
	public function &HeaderCentral () {
		return $this->_headerCentral;
	}

	/**
	 * @return ZIPArchiveHeaderFileLocal
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

		// TODO: versions, encryption, platform recognition
		/*
		 * https://pkware.cachefly.net/webdocs/casestudies/APPNOTE.TXT
		 *
		 0 - MS-DOS and OS/2 (FAT / VFAT / FAT32 file systems)
         1 - Amiga                     2 - OpenVMS
         3 - UNIX                      4 - VM/CMS
         5 - Atari ST                  6 - OS/2 H.P.F.S.
         7 - Macintosh                 8 - Z-System
         9 - CP/M                     10 - Windows NTFS
        11 - MVS (OS/390 - Z/OS)      12 - VSE
        13 - Acorn Risc               14 - VFAT
        15 - alternate MVS            16 - BeOS
        17 - Tandem                   18 - OS/400
        19 - OS X (Darwin)            20 thru 255 - unused
		 */

		return new QuarkArchiveItem(
			$this->_headerLocal->name,
			$this->_headerCentral->size_normal == $this->_headerCentral->size_compressed ? $this->_content : gzinflate($this->_content),
			QuarkDate::FromMSDOSDate($this->_headerLocal->date, $this->_headerLocal->time),
			$this->_headerCentral->size_normal,
			substr($this->_headerLocal->name, -1) == '/'
		);
	}

	/**
	 * @param ZIPArchiveHeaderFile $central = null
	 * @param string $data = ''
	 *
	 * @return ZIPArchiveItem
	 */
	public static function FromFileHeader (ZIPArchiveHeaderFile $central = null, $data = '') {
		if ($central == null) return null;

		/**
		 * @var ZIPArchiveHeaderFileLocal $local
		 */
		$local = QuarkObject::BinaryPopulate(
			new ZIPArchiveHeaderFileLocal(),
			substr($data, $central->local_header_offset)
		);

		$content = substr($data, $central->local_header_offset + $local->BinaryLength(), $central->size_compressed);

		$out = new self();
		$out->_headerCentral = $central;
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

		$out->_headerCentral = new ZIPArchiveHeaderFile();
		$out->_headerCentral->signature = ZIPArchive::SIGNATURE_CDFH;
		$out->_headerCentral->name = $item->Location();
		$out->_headerCentral->checksum = crc32($item->Content());
		$out->_headerCentral->level = $level;
		$out->_headerCentral->size_compressed = strlen($out->_content);
		$out->_headerCentral->size_normal = strlen($item->Content());
		$out->_headerCentral->version_extract = ZIPArchive::VERSION;
		$out->_headerCentral->version_origin = ZIPArchive::VERSION;
		$out->_headerCentral->length_name = strlen($out->_headerCentral->name);
		$out->_headerCentral->date = $date->Date;
		$out->_headerCentral->time = $date->Time;

		$out->_headerLocal = new ZIPArchiveHeaderFileLocal();
		$out->_headerLocal->signature = ZIPArchive::SIGNATURE_LFH;
		$out->_headerLocal->name = $item->Location();
		$out->_headerLocal->checksum = crc32($item->Content());
		$out->_headerLocal->level = $level;
		$out->_headerLocal->size_compressed = strlen($out->_content);
		$out->_headerLocal->size_normal = strlen($item->Content());
		$out->_headerLocal->version = ZIPArchive::VERSION;
		$out->_headerLocal->length_name = strlen($out->_headerLocal->name);
		$out->_headerLocal->length_extra = strlen($out->_headerLocal->extra);
		$out->_headerLocal->date = $date->Date;
		$out->_headerLocal->time = $date->Time;

		return $out;
	}
}