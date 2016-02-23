<?php
namespace Quark\Extensions\CDN\Providers;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;

use Quark\Extensions\CDN\IQuarkCDNProvider;

/**
 * Class NeutrinoCDN
 *
 * @package Quark\Extensions\CDN\Providers
 */
class NeutrinoCDN implements IQuarkCDNProvider {
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
	 * @var QuarkDTO $_response
	 */
	private $_response;

	/**
	 * @param string $appId
	 * @param string $appSecret
	 *
	 * @return mixed
	 */
	public function CDNApplication ($appId, $appSecret) {
		// TODO: Implement CDNApplication() method.
	}

	/**
	 * @return string
	 */
	public function ResourceGet () {
		$resource = $this->ResourceURL();

		if ($resource != '')
			$this->_file = QuarkHTTPClient::Download($resource);
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
}