<?php
namespace Quark\Extensions\Quark\Archives\ZIPArchive;

use Quark\IQuarkBinaryObject;

use Quark\QuarkBinaryObjectBehavior;
use Quark\QuarkField;

/**
 * Class ZIPArchiveHeaderLocal
 *
 * @package Quark\Extensions\Quark\Archives
 */
class ZIPArchiveHeaderLocal implements IQuarkBinaryObject {
	use QuarkBinaryObjectBehavior;

	/**
	 * @var string $signature = ''
	 */
	public $signature = '';

	/**
	 * @var int $version = 0
	 */
	public $version = 0;

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
	 * @var string $name = ''
	 */
	public $name = '';

	/**
	 * @var string $extra = ''
	 */
	public $extra = '';

	/**
	 * @return mixed
	 */
	public function BinaryFields () {
		return array(
			'signature' => QuarkField::BinaryHex(8),
			'version' => QuarkField::BinaryShort(false),
			'flags' => QuarkField::BinaryShort(false),
			'level' => QuarkField::BinaryShort(false),
			'time' => QuarkField::BinaryShort(false),
			'date' => QuarkField::BinaryShort(false),
			'checksum' => QuarkField::BinaryLong(false),
			'size_compressed' => QuarkField::BinaryLong(false),
			'size_normal' => QuarkField::BinaryLong(false),
			'length_name' => QuarkField::BinaryShort(false),
			'length_extra' => QuarkField::BinaryShort(false)
		);
	}

	/**
	 * @return int
	 */
	public function BinaryLength () {
		return $this->BinaryLengthCalculated()
			 + $this->length_name
			 + $this->length_extra;
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
	}

	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	public function BinaryExtract ($data) {
		return
			$this->name .
			$this->extra;
	}
}