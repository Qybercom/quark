<?php
namespace Quark\Extensions\Quark\REST;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;

use Quark\Quark;
use Quark\QuarkObject;

/**
 * Class RESTConfig
 *
 * @package Quark\Extensions\Quark\REST
 */
class RESTConfig implements IQuarkExtensionConfig {
	/**
	 * @var IQuarkRESTServiceDescriptor
	 */
	private $_descriptor;

	/**
	 * @var string
	 */
	private $_endpoint = '';

	/**
	 * @var string
	 */
	private $_source = '';

	/**
	 * @var string $_name
	 */
	private $_name = '';

	/**
	 * @param IQuarkRESTServiceDescriptor $descriptor
	 * @param string $endpoint
	 * @param string $source
	 */
	public function __construct (IQuarkRESTServiceDescriptor $descriptor = null, $endpoint = '', $source = '') {
		if (func_num_args() == 1)
			$endpoint = Quark::WebHost();

		if (strlen(trim($source)) == 0)
			$source = QuarkObject::ClassOf($descriptor);

		$this->_descriptor = $descriptor;
		$this->_endpoint = $endpoint;
		$this->_source = $source;
	}

	/**
	 * @param IQuarkRESTServiceDescriptor $descriptor
	 *
	 * @return IQuarkRESTServiceDescriptor
	 */
	public function Descriptor (IQuarkRESTServiceDescriptor $descriptor = null) {
		if (func_num_args() == 1)
			$this->_descriptor = $descriptor;

		return $this->_descriptor;
	}

	/**
	 * @param string $endpoint
	 *
	 * @return string
	 */
	public function Endpoint ($endpoint = '') {
		if (func_num_args() == 1)
			$this->_endpoint = $endpoint;

		return $this->_endpoint;
	}

	/**
	 * @param string $source
	 *
	 * @return string
	 */
	public function Source ($source = '') {
		if (func_num_args() == 1)
			$this->_source = $source;

		return $this->_source;
	}

	/**
	 * @param string $name
	 */
	public function Stacked ($name) {
		// TODO: Implement Stacked() method.
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		// TODO: Implement ExtensionInstance() method.
	}
}