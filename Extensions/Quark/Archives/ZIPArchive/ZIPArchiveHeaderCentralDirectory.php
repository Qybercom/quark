<?php
namespace Quark\Extensions\Quark\Archives\ZIPArchive;

use Quark\IQuarkBinaryObject;

use Quark\QuarkBinaryObjectBehavior;
use Quark\QuarkField;

/**
 * Class ZIPArchiveHeaderCentralDirectory
 *
 * @package Quark\Extensions\Quark\Archives
 */
class ZIPArchiveHeaderCentralDirectory implements IQuarkBinaryObject {
	use QuarkBinaryObjectBehavior;

	/**
	 * @var string $signature = ''
	 */
	public $signature = '';

	/**
	 * @var int $version_origin = 0
	 */
	public $version_origin = 0;

	/**
	 * @var int $version_extract = 0
	 */
	public $version_extract = 0;

	/**
	 * @var int $flags = 0
	 */
	public $flags = 0;

	/**
	 * @var int $level = 0
	 */
	public $level = 0;

	/**
	 * @var int $time = 0
	 */
	public $time = 0;

	/**
	 * @var int $date = 0
	 */
	public $date = 0;

	/**
	 * @var int $checksum = 0
	 */
	public $checksum = 0;

	/**
	 * @var int $size_compressed = 0
	 */
	public $size_compressed = 0;

	/**
	 * @var int $size_normal = 0
	 */
	public $size_normal = 0;

	/**
	 * @var int $length_name = 0
	 */
	public $length_name = 0;

	/**
	 * @var int $length_extra = 0
	 */
	public $length_extra = 0;

	/**
	 * @var int $length_comment = 0
	 */
	public $length_comment = 0;

	/**
	 * @var int $disk = 0
	 */
	public $disk = 0;

	/**
	 * @var int $attributes_internal = 0
	 */
	public $attributes_internal = 0;

	/**
	 * @var int $attributes_external = 0
	 */
	public $attributes_external = 0;

	/**
	 * @var int $local_header_offset = 0
	 */
	public $local_header_offset = 0;

	/**
	 * @var string $name = ''
	 */
	public $name = '';

	/**
	 * @var string $extra = ''
	 */
	public $extra = '';

	/**
	 * @var string $comment = ''
	 */
	public $comment = '';

	/**
	 * @return mixed
	 */
	public function BinaryFields () {
		return array(
			'signature' => QuarkField::BinaryHex(8),
			'version_origin' => QuarkField::BinaryShort(false),
			'version_extract' => QuarkField::BinaryShort(false),
			'flags' => QuarkField::BinaryShort(false),
			'level' => QuarkField::BinaryShort(false),
			'time' => QuarkField::BinaryShort(false),
			'date' => QuarkField::BinaryShort(false),
			'checksum' => QuarkField::BinaryLong(false),
			'size_compressed' => QuarkField::BinaryLong(false),
			'size_normal' => QuarkField::BinaryLong(false),
			'length_name' => QuarkField::BinaryShort(false),
			'length_extra' => QuarkField::BinaryShort(false),
			'length_comment' => QuarkField::BinaryShort(false),
			'disk' => QuarkField::BinaryShort(false),
			'attributes_internal' => QuarkField::BinaryShort(false),
			'attributes_external' => QuarkField::BinaryLong(false),
			'local_header_offset' => QuarkField::BinaryLong(false)
		);
	}

	/**
	 * @return int
	 */
	public function BinaryLength () {
		return $this->BinaryLengthCalculated()
			 + $this->length_name
			 + $this->length_extra
			 + $this->length_comment;
	}

	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	public function BinaryPopulate ($data) {
		$offset = $this->BinaryLengthCalculated();

		$this->name = substr($data, $offset, $this->length_name);
		$this->extra = substr($data, $offset + $this->length_name, $this->length_extra);
		$this->comment = substr($data, $offset + $this->length_name + $this->length_extra, $this->length_comment);
	}

	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	public function BinaryExtract ($data) {
		return
			$this->name .
			$this->extra .
			$this->comment;
	}
}