<?php
namespace Quark\Extensions\Quark\Archives\ZIPArchive;

use Quark\IQuarkArchive;

use Quark\QuarkArchiveItem;
use Quark\QuarkObject;

/**
 * Class ZIPArchive
 *
 * https://blog2k.ru/archives/3391
 * https://blog2k.ru/archives/3392
 * http://www.sql.ru/forum/1053806/razbor-zip-fayla
 * https://stackoverflow.com/questions/2384/read-binary-file-into-a-struct
 * http://php.net/manual/ru/class.ziparchive.php#118392
 *
 * https://users.cs.jmu.edu/buchhofp/forensics/formats/pkzip.html
 *
 * https://github.com/maennchen/ZipStream-PHP/blob/master/src/ZipStream.php
 *
 * @package Quark\Extensions\Quark\Archives\ZIPArchive
 */
class ZIPArchive implements IQuarkArchive {
	const SIGNATURE_CDFH = '504b0102';
	const SIGNATURE_LFH  = '504b0304';
	const SIGNATURE_EOCD = '504b0506';
	const SIGNATURE_DD   = '504b0708';

	const VERSION = 14;

	/**
	 * @var int $_level = 0
	 */
	private $_level = 0;

	/**
	 * @param int $level = 0
	 */
	public function __construct ($level = 0) {
		$this->Level($level);
	}

	/**
	 * @param int $level = 0
	 *
	 * @return int
	 */
	public function Level ($level = 0) {
		if (func_num_args() != 0)
			$this->_level = $level;

		return $this->_level;
	}

	/**
	 * @param QuarkArchiveItem[] $items
	 *
	 * @return string
	 */
	public function Pack ($items) {
		$meta = new ZIPArchiveHeaderCentralDirectoryEnd();

		$meta->signature = self::SIGNATURE_EOCD;
		$meta->central_directory_count_current = sizeof($items);
		$meta->central_directory_count_all = sizeof($items);

		$bufferLocal = '';
		$bufferCentral = '';

		foreach ($items as $i => &$item) {
			$zipItem = ZIPArchiveItem::FromQuarkArchiveItem($item, $this->_level);

			$zipItemCentral = $zipItem->HeaderCentral();
			$zipItemCentral->local_header_offset = strlen($bufferLocal);

			$zipItemLocal = $zipItem->HeaderLocal();

			$bufferLocal .= QuarkObject::BinaryExtract($zipItemLocal) . $zipItem->Content();
			$bufferCentral .= QuarkObject::BinaryExtract($zipItemCentral);
		}

		$meta->central_directory_offset = strlen($bufferLocal);
		$meta->central_directory_length = strlen($bufferCentral);

		return $bufferLocal . $bufferCentral . QuarkObject::BinaryExtract($meta);
	}

	/**
	 * @param string $data
	 *
	 * @return QuarkArchiveItem[]
	 */
	public function Unpack ($data) {
		$meta_pos = strpos($data, hex2bin(self::SIGNATURE_EOCD));
		if ($meta_pos === false) return null;

		/**
		 * @var ZIPArchiveHeaderCentralDirectoryEnd $meta_obj
		 */
		$meta_obj = QuarkObject::BinaryPopulate(new ZIPArchiveHeaderCentralDirectoryEnd(), substr($data, $meta_pos));

		$i = 0;
		$cursor = $meta_obj->central_directory_offset;
		$out = array();

		while ($i < $meta_obj->central_directory_count_current) {
			/**
			 * @var ZIPArchiveHeaderFile $record
			 */
			$record = QuarkObject::BinaryPopulate(
				new ZIPArchiveHeaderFile(),
				substr($data, $cursor)
			);

			$item = ZIPArchiveItem::FromFileHeader($record, $data);

			if ($item != null)
				$out[] = $item->QuarkArchive();

			$cursor += $record->BinaryLength();
			$i++;
		}

		return $out;
	}
}