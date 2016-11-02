<?php
namespace Quark\Extensions\Quark\MicroService;

use Quark\IQuarkExtension;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkHTTPClient;
use Quark\QuarkKeyValuePair;

/**
 * Class MicroService
 *
 * @package Quark\Extensions\Quark\MicroService
 */
class MicroService implements IQuarkExtension {
	/**
	 * @var MicroServiceConfig $_config
	 */
	private $_config;

	/**
	 * @param string $config
	 */
	public function __construct ($config) {
		$this->_config = Quark::Config()->Extension($config);
	}

	/**
	 * @param QuarkDTO $request
	 *
	 * @return bool
	 */
	public function Auth (QuarkDTO $request) {
		return $this->_config->Provider()->MicroServiceAuth(
			new QuarkKeyValuePair($request->appId, $request->appToken),
			new QuarkKeyValuePair($this->_config->appId, $this->_config->appSecret)
		);
	}

	/**
	 * @param string $appId = ''
	 * @param string $url = ''
	 * @param QuarkDTO $request = null
	 * @param QuarkDTO $response = null
	 *
	 * @return QuarkDTO|bool
	 */
	public function Invoke ($appId = '', $url = '', QuarkDTO $request = null, QuarkDTO $response = null) {
		if ($request == null) $request = QuarkDTO::ForGET();
		if ($response == null) $response = new QuarkDTO(new QuarkJSONIOProcessor());

		$provider = $this->_config->Provider();

		$request->Processor(new QuarkFormIOProcessor());
		$request->Merge(array(
			'appId' => $this->_config->appId,
			'appToken' => $provider->MicroServiceToken(
				$appId,
				new QuarkKeyValuePair($this->_config->appId, $this->_config->appSecret)
			)
		));

		if ($provider instanceof IQuarkMicroServiceProviderWithCustomInvoke) {
			$_request = $provider->MicroServiceRequest($request);
			if ($_request !== null) $request->Merge($_request);

			$_response = $provider->MicroServiceResponse($response);
			if ($_response !== null) $response->Merge($_response);
		}

		return QuarkHTTPClient::To(
			$provider->MicroServiceEndpoint($appId) . $url,
			$request,
			$response
		);
	}
}