<?php
namespace Quark\Extensions\UPnP;

use Quark\IQuarkExtension;

use Quark\Quark;
use Quark\QuarkDate;
use Quark\QuarkDTO;
use Quark\QuarkURI;
use Quark\QuarkXMLIOProcessor;
use Quark\QuarkXMLNode;

use Quark\Extensions\Quark\SOAP\SOAPEnvelope;

/**
 * Class UPnP
 *
 * @package Quark\Extensions\UPnP
 */
class UPnP implements IQuarkExtension {
	const SOAP_KEY = 'SOAP-ENV';

	/**
	 * @var UPnPConfig $_config
	 */
	private $_config;

	/**
	 * @param string $config = ''
	 */
	public function __construct ($config = '') {
		$this->_config = Quark::Config()->Extension($config);
	}

	/**
	 * @return UPnPConfig
	 */
	public function &Config () {
		return $this->_config;
	}

	/**
	 * @return UPnPRootDescription
	 */
	public function RootDescription () {
		return $this->_config->RootDescription();
	}

	/**
	 * @param string $name = ''
	 *
	 * @return UPnPServiceControlProtocol
	 */
	public function ServiceControlProtocol ($name = '') {
		return $this->_config->ServiceControlProtocol($name);
	}

	/**
	 * @return IQuarkUPnPProvider[]
	 */
	public function Providers () {
		return $this->_config->Providers();
	}

	/**
	 * @param IQuarkUPnPProviderServiceControlDTO $context = null
	 * @param QuarkDTO $request = null
	 *
	 * @return IQuarkUPnPProviderServiceControlDTO
	 */
	public function ServiceControlRequest (IQuarkUPnPProviderServiceControlDTO $context = null, QuarkDTO $request = null) {
		return $context != null && $request != null ? $context->UPnPProviderServiceControlRequest($request) : null;
	}

	/**
	 * @param IQuarkUPnPProviderServiceControlDTO $dto = null
	 *
	 * @return QuarkXMLNode
	 */
	public function ServiceControlResponse (IQuarkUPnPProviderServiceControlDTO $dto = null) {
		$soap = new SOAPEnvelope(self::SOAP_KEY);
		$soap->Body($dto == null ? null : array($dto->UPnPProviderServiceControlResponse()));

		return $soap->Response();
	}

	/**
	 * @param IQuarkUPnPProviderServiceControlDTO $dto = null
	 *
	 * @return QuarkDTO
	 */
	public function ServiceControlResponseDTO (IQuarkUPnPProviderServiceControlDTO $dto = null) {
		$response = QuarkDTO::ForResponse(new QuarkXMLIOProcessor());

		$response->Header('EXT', '');
		$response->Header('DATE', QuarkDate::GMTNow()->Format(QuarkDate::FORMAT_HTTP_DATE) . ' GMT');
		$response->Header('SERVER', $this->_config->RootDescription()->ServerName());

		$response->Data($this->ServiceControlResponse($dto));

		return $response;
	}

	/**
	 * @param string $host = QuarkURI::HOST_LOCALHOST
	 *
	 * @return UPnPAnnouncer
	 */
	public function Announcer ($host = QuarkURI::HOST_LOCALHOST) {
		return new UPnPAnnouncer($this->_config->RootDescription(), func_num_args() != 0 ? $host : $this->_config->AnnouncerHost());
	}
}