<?php
namespace Quark\Extensions\CDN\Providers;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkFile;
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
	const ACTION_CREATE = 'create/';
	const ACTION_UPDATE = 'update/';
	const ACTION_REMOVE = 'remove/';

	const ENDPOINT_API = 'http://api.ncdn/';
	const ENDPOINT_CDN = 'http://cdn.ncdn/';

	/**
	 * @var string $_appId = ''
	 */
	private $_appId = '';

	/**
	 * @var string $_appSecret = ''
	 */
	private $_appSecret = '';

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
		$this->_appId = $appId;
		$this->_appSecret = $appSecret;
	}

	/**
	 * @param string $id
	 *
	 * @return string
	 */
	public function CDNResourceURL ($id) {
		return self::ENDPOINT_CDN . $this->_appId . '/' . $id;
	}

	/**
	 * @param QuarkFile $file
	 *
	 * @return string
	 */
	public function CDNResourceCreate (QuarkFile $file) {
		$ok = $this->API(self::ACTION_CREATE, '', QuarkDTO::METHOD_POST, array(
			'resource' => $file
		));

		if (!$ok) return false;

		return $this->_response->resource;
	}

	/**
	 * @param string $id
	 * @param QuarkFile $file
	 *
	 * @return bool
	 */
	public function CDNResourceUpdate ($id, QuarkFile $file) {
		return $this->API(self::ACTION_UPDATE, $id, QuarkDTO::METHOD_POST, array(
			'resource' => $file
		));
	}

	/**
	 * @param string $id
	 *
	 * @return bool
	 */
	public function CDNResourceDelete ($id) {
		return $this->API(self::ACTION_REMOVE, $id, QuarkDTO::METHOD_GET);
	}

	/**
	 * @param string $action = self::ACTION_GET
	 * @param string $method = QuarkDTO::METHOD_GET
	 * @param string $resource = ''
	 * @param array $data = []
	 *
	 * @return bool
	 */
	public function API ($action = self::ACTION_GET, $resource = '', $method = QuarkDTO::METHOD_GET, $data = []) {
		$url = self::ENDPOINT_API . 'resource/' . $action . '/' . $resource;
		$data['access'] = sha1($this->_appId . $this->_appSecret);

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
			return $this->_err('NeutrinoCDN: Resource "' . $resource . '" not found');

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