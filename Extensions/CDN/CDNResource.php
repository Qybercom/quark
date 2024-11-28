<?php
namespace Quark\Extensions\CDN;

use Quark\IQuarkExtension;
use Quark\IQuarkModel;
use Quark\IQuarkModelWithAfterPopulate;
use Quark\IQuarkStrongModel;
use Quark\IQuarkLinkedModel;
use Quark\IQuarkStrongModelWithRuntimeFields;

use Quark\Quark;
use Quark\QuarkException;
use Quark\QuarkFile;
use Quark\QuarkHTTPClient;
use Quark\QuarkModel;
use Quark\QuarkModelBehavior;

/**
 * Class CDNResource
 *
 * @property string $resource = ''
 *
 * @property string $url = ''
 *
 * @package Quark\Extensions\CDN
 */
class CDNResource implements IQuarkExtension, IQuarkModel, IQuarkStrongModel, IQuarkStrongModelWithRuntimeFields, IQuarkLinkedModel, IQuarkModelWithAfterPopulate {
	use QuarkModelBehavior;

	/**
	 * @var CDNConfig $_config
	 */
	private $_config;

	/**
	 * @var QuarkFile $_fallback
	 */
	private $_fallback;
	
	/**
	 * @var string $_fallbackURL
	 */
	private $_fallbackURL;

	/**
	 * @param string $config
	 * @param string $fallback = ''
	 * @param string $fallbackURL = ''
	 */
	public function __construct ($config, $fallback = null, $fallbackURL = null) {
		$this->_config = Quark::Config()->Extension($config);
		$this->_fallback = $fallback === null ? null : new QuarkFile($fallback);
		$this->_fallbackURL = $fallbackURL;
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
			: $this->_fallback;
	}
	
	/**
	 * @return QuarkFile
	 */
	public function Fallback () {
		return $this->_fallback;
	}
	
	/**
	 * @return string
	 */
	public function FallbackURL () {
		return $this->_fallbackURL;
	}

	/**
	 * @param QuarkFile $fallback = null
	 *
	 * @return string
	 */
	public function URL (QuarkFile $fallback = null) {
		if (!isset($this->resource) || $this->resource == null) {
			if ($this->_fallback != null)
				return $this->_fallback->WebLocation();
			
			return $this->_fallbackURL;
		}

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
		$this->url = $this->URL();

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
		return $this->URL($this->_fallback) != $this->_fallback->WebLocation();
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
	 * @return void
	 */
	public function Rules () {
		// TODO: Implement Rules() method.
	}

	/**
	 * @return mixed
	 */
	public function RuntimeFields () {
		return array(
			'url' => ''
		);
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

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function AfterPopulate ($raw) {
		$this->url = $this->URL();
	}
}