<?php
namespace Quark\Extensions\CDN;

use Quark\IQuarkLinkedModel;
use Quark\IQuarkModel;
use Quark\IQuarkStrongModel;

use Quark\Quark;
use Quark\QuarkURI;
use Quark\QuarkFile;

/**
 * Class CDNResource
 *
 * @property string $resource = ''
 *
 * @package Quark\Extensions\CDN
 */
class CDNResource extends QuarkFile implements IQuarkModel, IQuarkStrongModel, IQuarkLinkedModel {
	/**
	 * @var CDNConfig $_config
	 */
	private $_config;

	/**
	 * @var QuarkFile $_fallback
	 */
	private $_fallback;

	/**
	 * @var QuarkFile $_file
	 */
	private $_file;

	/**
	 * @param string $config
	 * @param string $fallback = ''
	 */
	public function __construct ($config, $fallback = '') {
		parent::__construct();

		$this->_config = Quark::Config()->Extension($config);
		$this->_fallback = new QuarkFile($fallback);
	}

	/**
	 * @return string
	 */
	public function __toString () {
		return $this->WebLocation();
	}

	/**
	 * @return QuarkFile
	 * @throws \Quark\QuarkArchException
	 */
	private function _file () {
		if ($this->_file == null) {
			$this->_file = $this->_config->CDN()->ResourceGet();
		}

		if ($this->_file == null) {
			$this->_file = clone $this->_fallback;
			$this->_file->Load();
		}

		return $this->_file;
	}

	/**
	 * @param QuarkFile $file
	 *
	 * @return QuarkFile
	 */
	public function File (QuarkFile $file = null) {
		$this->_file();

		if (func_num_args() != 0)
			$this->_file = $file;

		return $this->_file;
	}

	/**
	 * @param string $content
	 *
	 * @return string
	 */
	public function Content ($content = '') {
		$this->_file();

		if (func_num_args() != 0)
			$this->_file->Content($content);

		return $this->_file->Content();
	}

	/**
	 * @return string
	 */
	public function WebLocation () {
		return $this->_file()->WebLocation();
	}

	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'resource' => ''
		);
	}

	/**
	 * @return mixed
	 */
	public function Rules () {
		// TODO: Implement Rules() method.
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Link ($raw) {
		$uri = QuarkURI::FromURI($raw);

		if ($uri == null) return null;

		$this->resource = $uri->Route(1);
	}

	/**
	 * @return mixed
	 */
	public function Unlink () {
		return '';//$this->_config->ResourceURL($this->resource);
	}
}