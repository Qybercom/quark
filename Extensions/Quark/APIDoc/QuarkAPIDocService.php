<?php
namespace Quark\Extensions\Quark\APIDoc;

use Quark\QuarkObject;

/**
 * Class QuarkAPIDocService
 *
 * @package Quark\Extensions\Quark\APIDoc
 */
class QuarkAPIDocService {
	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var string $_description = ''
	 */
	private $_description = '';

	/**
	 * @var string $_package = ''
	 */
	private $_package = '';

	/**
	 * @var QuarkAPIDocServiceMethod[] $_methods = []
	 */
	private $_methods = array();

	/**
	 * @param string $name = ''
	 * @param string $description = ''
	 * @param string $package = ''
	 * @param QuarkAPIDocServiceMethod[] $methods = []
	 */
	public function __construct ($name = '', $description = '<i>No description</i>', $package = '', $methods = []) {
		$this->_name = $name;
		$this->_description = strlen($description) == 0
			? '<i>No description</i>'
			:  $description;

		$this->_package = $package;

		if (QuarkObject::IsArrayOf($methods, new QuarkAPIDocServiceMethod()))
			$this->_methods = $methods;
	}

	/**
	 * @return string
	 */
	public function Name () {
		return $this->_name;
	}

	/**
	 * @return string
	 */
	public function Description () {
		return $this->_description;
	}

	/**
	 * @return string
	 */
	public function Package () {
		return $this->_package;
	}

	/**
	 * @return QuarkAPIDocServiceMethod[]
	 */
	public function Methods () {
		return $this->_methods;
	}

	/**
	 * @return string
	 */
	public function CanonicalName () {
		return str_replace('\\', '/', str_replace('Services', '', $this->Package()) . '\\' . $this->_name);
	}

	/**
	 * @param string[] $params = []
	 *
	 * @return string
	 */
	public function Endpoint ($params = []) {
		$route = '';

		if (is_array($params))
			foreach ($params as $param)
				$route .= '/<' . $param . '>';

		$endpoint = str_replace('//', '/', '/' . str_replace('\\', '/', str_replace('Services', '', $this->Package())) . '/' . str_replace('Service', '', $this->_name));
		
		return self::EndpointOf($endpoint) . $route;
	}

	/**
	 * @param string $url = ''
	 *
	 * @return string
	 */
	public static function EndpointOf ($url = '') {
		$out = array();
		$path = explode('/', $url);

		foreach ($path as $component)
			$out[] = preg_match_all('#[A-Z]#', $component) > 1 ? $component : strtolower($component);

		return implode('/', $out);
	}
}