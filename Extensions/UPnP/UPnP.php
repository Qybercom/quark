<?php
namespace Quark\Extensions\UPnP;

use Quark\IQuarkExtension;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkURI;
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
	 * @param IQuarkUPnPProviderServiceControlDTO $response = null
	 *
	 * @return QuarkXMLNode
	 */
	public function ServiceControlResponse (IQuarkUPnPProviderServiceControlDTO $response = null) {
		$soap = new SOAPEnvelope(self::SOAP_KEY);
		$soap->Body(array($response->UPnPProviderServiceControlResponse()));

		return $soap->Response();
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