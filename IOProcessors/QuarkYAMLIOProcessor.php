<?php
namespace Quark\IOProcessors;

use Quark\IQuarkIOProcessor;

/**
 * Class QuarkYAMLIOProcessor
 *
 * @package Quark\IOProcessors
 */
class QuarkYAMLIOProcessor implements IQuarkIOProcessor {
	const MIME = 'text/yaml';

	/**
	 * @return string
	 */
	public function MimeType () { return self::MIME; }

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
	 * @param bool $fallback
	 *
	 * @return mixed
	 */
	public function Batch ($raw, $fallback) {
		return array($raw);
	}
}