<?php
namespace Quark\Extensions\CDN\NeutrinoCDN;

use Quark\IQuarkExtension;
use Quark\IQuarkLinkedModel;
use Quark\IQuarkModel;
use Quark\IQuarkStrongModel;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkFile;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkURI;

/**
 * Class NeutrinoCDN
 *
 * @property string $resource = ''
 *
 * @package Quark\Extensions\CDN\NeutrinoCDN
 */
class NeutrinoCDN extends QuarkFile implements IQuarkExtension, IQuarkModel, IQuarkStrongModel, IQuarkLinkedModel {
	const ACTION_GET = '';
	const ACTION_URL = 'endpoint/';
	const ACTION_CREATE = 'create/';
	const ACTION_UPDATE = 'update/';
	const ACTION_REMOVE = 'remove/';

	/**
	 * @var string $resource = ''
	 */
	public $resource = '';

	/**
	 * @var NeutrinoCDNConfig $_config
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
	 * @var QuarkDTO $_response
	 */
	private $_response;

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
	 * @return string
	 */
	public function ResourceURL () {
		return $this->API(self::ACTION_URL) ? $this->_response->url : '';
	}

	/**
	 * @return bool
	 */
	public function ResourceCreate () {
		if ($this->_file == null) return false;

		$ok = $this->API(self::ACTION_CREATE, QuarkDTO::METHOD_POST, array(
			'resource' => $this->_file
		));

		if (!$ok) return false;

		$this->resource = $this->_response->resource;
		return $ok;
	}

	/**
	 * @return bool
	 */
	public function ResourceUpdate () {
		if ($this->_file == null) return false;

		return $this->API(self::ACTION_UPDATE, QuarkDTO::METHOD_POST, array(
			'resource' => $this->_file
		));
	}

	/**
	 * @return bool
	 */
	public function ResourceCommit () {
		if ($this->_file == null) return false;

		return $this->API($this->resource == '' ? self::ACTION_CREATE : self::ACTION_UPDATE, QuarkDTO::METHOD_POST, array(
			'resource' => $this->_file
		));
	}

	/**
	 * @return bool
	 */
	public function ResourceRemove () {
		if ($this->_file == null) return false;

		return $this->API(self::ACTION_REMOVE);
	}

	/**
	 * @param string $action = self::ACTION_GET
	 * @param string $method = QuarkDTO::METHOD_GET
	 * @param array $data = []
	 *
	 * @return bool
	 */
	public function API ($action = self::ACTION_GET, $method = QuarkDTO::METHOD_GET, $data = []) {
		$url = $this->_config->ResourceURL($this->resource, $action);
		$data['access'] = $this->_config->appSecret;

		$request = new QuarkDTO(new QuarkJSONIOProcessor());
		$request->Method($method);
		$request->Data($data);

		$this->_response = new QuarkDTO(new QuarkJSONIOProcessor());

		$cdn = QuarkHTTPClient::To($url, $request, $this->_response);

		if (!isset($cdn->status))
			return $this->_err('NeutrinoCDN: API unreachable');

		if ($cdn->status == 403)
			return $this->_err('NeutrinoCDN: Access denied for url ' . $url);

		if ($cdn->status == 404)
			return $this->_err('NeutrinoCDN: Resource "' . $this->resource . '" not found');

		if ($cdn->status == 500)
			return $this->_err('NeutrinoCDN: Internal server error');

		if ($cdn->status != 200)
			return $this->_err('NeutrinoCDN: Unknown error. Status: ' . $cdn->status);

		return true;
	}

	/**
	 * @param string $msg
	 *
	 * @return bool
	 */
	private function _err ($msg) {
		Quark::Log($msg);
		return false;
	}

	/**
	 * @return QuarkFile
	 * @throws \Quark\QuarkArchException
	 */
	private function _file () {
		if ($this->_file == null) {
			$resource = $this->ResourceURL();

			if ($resource != '')
				$this->_file = QuarkHTTPClient::Download($resource);
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