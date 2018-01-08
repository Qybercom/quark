<?php
namespace Quark\Extensions\Quark\Archives\ZIPArchive;

use Quark\IQuarkBinaryObject;

use Quark\QuarkBinaryObjectBehavior;
use Quark\QuarkField;

/**
 * Class ZIPArchiveHeaderCentralDirectoryEnd
 *
 * @package Quark\Extensions\Quark\Archives
 */
class ZIPArchiveHeaderCentralDirectoryEnd implements IQuarkBinaryObject {
	use QuarkBinaryObjectBehavior;

	/**
	 * @var string $signature = ''
	 */
	public $signature = '';

	/**
	 * @var int $disk_current = 0
	 */
	public $disk_current = 0;

	/**
	 * @var int $disk_start = 0
	 */
	public $disk_start = 0;

	/**
	 * @var int $central_directory_count_current = 0
	 */
	public $central_directory_count_current = 0;

	/**
	 * @var int $central_directory_count_all = 0
	 */
	public $central_directory_count_all = 0;

	/**
	 * @var int $central_directory_length = 0
	 */
	public $central_directory_length = 0;

	/**
	 * @var int $central_directory_offset = 0
	 */
	public $central_directory_offset = 0;

	/**
	 * @var int $comment_length = 0
	 */
	public $comment_length = 0;

	/**
	 * @var string $comment_text = ''
	 */
	public $comment_text = '';

	/**
	 * @return mixed
	 */
	public function BinaryFields () {
		return array(
			'signature' => QuarkField::BinaryHex(8),
			'disk_current' => QuarkField::BinaryShort(false),
			'disk_start' => QuarkField::BinaryShort(false),
			'central_directory_count_current' => QuarkField::BinaryShort(false),
			'central_directory_count_all' => QuarkField::BinaryShort(false),
			'central_directory_length' => QuarkField::BinaryLong(false),
			'central_directory_offset' => QuarkField::BinaryLong(false),
			'comment_length' => QuarkField::BinaryShort(false),
			'comment_text' => QuarkField::BinaryString()
		);
	}

	/**
	 * @return int
	 */
	public function BinaryLength () {
		return $this->BinaryLengthCalculated()
			 + $this->comment_length;
	}

	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	public function BinaryPopulate ($data) {
		// TODO: Implement BinaryPopulate() method.
	}

	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	public function BinaryExtract ($data) {
		return $this->comment_text;
	}
}