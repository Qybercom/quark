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
	
	const TRUE_TRUE = 'true';
	const TRUE_YES = 'yes';
	const FALSE_FALSE = 'false';
	const FALSE_NO = 'no';
	const NULL_NULL = 'null';
	const NULL_TILDA = '~';
	
	const BATCH = '#\n?---(.*)\n#Uis';
	//const ELEM = '#(.+)\:(([^\n]*)|(\s{2,}(.*)))\n#Uis';
	const ELEM = '#(\s{2,})?(.+)\:(.*)\n#Uis';

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
		//preg_match_all(self::ELEM, $raw . "\n", $found, PREG_SET_ORDER);
		
		//print_r($found);
		$lines = explode();
	}

	/**
	 * @param string $raw
	 *
	 * @return mixed
	 */
	public function Batch ($raw) {
		return preg_split(self::BATCH, "\n" . $raw);
	}
}