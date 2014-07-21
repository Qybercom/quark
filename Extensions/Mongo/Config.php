<?php
namespace Quark\Extensions\Mongo;

use Quark\QuarkCredentials;
use Quark\IQuarkExtensionConfig;

/**
 * Class Config
 * @package Quark\Extensions\Mongo
 */
class Config implements IQuarkExtensionConfig {
	private $_pool = array();

	/**
	 * @return string
	 */
	public function AssignedExtension () {
		return 'Quark\Extensions\Mongo\Source';
	}

	/**
	 * @return array
	 */
	public function Pool () {
		return $this->_pool;
	}

	/**
	 * @param string $name
	 * @param QuarkCredentials $credentials
	 */
	public function Source ($name, QuarkCredentials $credentials) {
		$this->_pool[$name] = $credentials;
	}
}