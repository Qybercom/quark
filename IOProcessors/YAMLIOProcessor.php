<?php
namespace Quark\IOProcessors;

use Quark\IQuarkIOProcessor;

/**
 * Class YAMLIOProcessor
 *
 * @package Quark\IOProcessors
 */
class YAMLIOProcessor implements IQuarkIOProcessor {
	const MIME = 'text/yaml';

	/**
	 * @return string
	 */
	public function MimeType () {
		return self::MIME;
	}

	/**
	 * @param $data
	 *
	 * @return string
	 */
	public function Encode ($data) {
		// TODO: Implement Encode() method.
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Decode ($raw) {
		// TODO: Implement Decode() method.
	}

	/**
	 * @param string $raw
	 *
	 * @return mixed
	 */
	public function Batch ($raw) {
		// TODO: Implement Batch() method.
	}
}