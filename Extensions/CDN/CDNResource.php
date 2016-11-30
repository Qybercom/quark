<?php
namespace Quark\Extensions\CDN;

use Quark\IQuarkExtension;
use Quark\IQuarkModel;
use Quark\IQuarkStrongModel;
use Quark\IQuarkLinkedModel;

use Quark\Quark;
use Quark\QuarkException;
use Quark\QuarkFile;
use Quark\QuarkHTTPClient;
use Quark\QuarkModel;

/**
 * Class CDNResource
 *
 * @property string $resource = ''
 *
 * @package Quark\Extensions\CDN
 */
class CDNResource implements IQuarkExtension, IQuarkModel, IQuarkStrongModel, IQuarkLinkedModel {
	/**
	 * @var CDNConfig $_config
	 */
	private $_config;

	/**
	 * @var QuarkFile $_default
	 */
	private $_default;

	/**
	 * @param string $config
	 * @param string $fallback = ''
	 */
	public function __construct ($config, $fallback = '') {
		$this->_config = Quark::Config()->Extension($config);
		$this->_default = new QuarkFile($fallback);
	}

	/**
	 * @return string
	 */
	public function __toString () {
		try {
			return $this->URL();
		}
		catch (QuarkException $e) {
			Quark::LogException($e);
			return '';
		}
	}

	/**
	 * @return QuarkFile
	 */
	public function File () {
		return $this->resource != null
			? QuarkHTTPClient::Download($this->URL())
			: $this->_default;
	}

	/**
	 * @param QuarkFile $fallback = null
	 *
	 * @return string
	 */
	public function URL (QuarkFile $fallback = null) {
		if (!isset($this->resource) || $this->resource == null)
			return $this->_default->WebLocation();

		$url = $this->_config->CDNProvider()->CDNResourceURL($this->resource);

		return $url ? $url : ($fallback ? $fallback->WebLocation() : '');
	}

	/**
	 * @param QuarkFile $file = null
	 * @param bool $force = true
	 *
	 * @return bool
	 */
	public function Commit (QuarkFile $file = null, $force = true) {
		if ($file == null) return false;

		if (isset($this->resource) && $this->resource != null) {
			$update = $this->_config->CDNProvider()->CDNResourceUpdate($this->resource, $file);

			if ($update) return $update;
			if (!$force) return false;
		}
		
		$id = $this->_config->CDNProvider()->CDNResourceCreate($file);
		if (!$id) return false;

		$this->resource = $id;
		return true;
	}

	/**
	 * @return bool
	 */
	public function Erase () {
		return $this->resource != null
			? $this->_config->CDNProvider()->CDNResourceDelete($this->resource)
			: false;
	}

	/**
	 * @param string $config
	 * @param string $id
	 * @param string $fallback = ''
	 *
	 * @return QuarkModel|CDNResource
	 */
	public static function ById ($config, $id, $fallback = '') {
		return new QuarkModel(new self($config, $fallback), array(
			'resource' => $id
		));
	}

	/**
	 * @return bool
	 */
	public function Exists () {
		return $this->URL($this->_default) != $this->_default->WebLocation();
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
		return new QuarkModel($this, array(
			'resource' => $raw
		));
	}

	/**
	 * @return mixed
	 */
	public function Unlink () {
		return $this->resource;
	}
}