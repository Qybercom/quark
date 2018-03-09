<?php
namespace Quark\Extensions\UPnP\Providers\DLNA\ServiceControlDTO;

use Quark\QuarkDTO;
use Quark\QuarkXMLNode;

use Quark\Extensions\Quark\SOAP\SOAPElement;
use Quark\Extensions\Quark\SOAP\SOAPEnvelope;

use Quark\Extensions\UPnP\IQuarkUPnPProviderServiceControlDTO;
use Quark\Extensions\UPnP\Providers\DLNA\Services\DLNAServiceXMSMediaReceiverRegistrar;

/**
 * Class DLNAServiceControlDTOIsAuthorized
 *
 * @package Quark\Extensions\UPnP\Providers\DLNA\ServiceControlDTO
 */
class DLNAServiceControlDTOIsAuthorized implements IQuarkUPnPProviderServiceControlDTO {
	/**
	 * @var string $_device = ''
	 */
	private $_device = '';

	/**
	 * @var bool $_authorized = true
	 */
	private $_authorized = true;

	/**
	 * @param string $device = ''
	 *
	 * @return string
	 */
	public function Device ($device = '') {
		if (func_num_args() != 0)
			$this->_device = $device;

		return $this->_device;
	}

	/**
	 * @param bool $authorized = true
	 *
	 * @return bool
	 */
	public function Authorized ($authorized = true) {
		if (func_num_args() != 0)
			$this->_authorized = $authorized;

		return $this->_authorized;
	}

	/**
	 * @param QuarkDTO $request
	 *
	 * @return IQuarkUPnPProviderServiceControlDTO
	 */
	public function UPnPProviderServiceControlRequest (QuarkDTO $request) {
		$soap = SOAPEnvelope::FromRequest($request);
		if ($soap == null) return null;

		$body = $soap->Body();
		if (sizeof($body) == 0) return null;

		$auth = $body[0];
		if ($auth->Name() != 'IsAuthorized') return null;

		$data = $auth->Data();

		if (isset($data->DeviceID))
			$this->_device = $data->DeviceID instanceof QuarkXMLNode
				? $data->DeviceID->Data()
				: $data->DeviceID;

		return $this;
	}

	/**
	 * @return SOAPElement[]
	 */
	public function UPnPProviderServiceControlResponse () {
		return new SOAPElement('u', 'IsAuthorizedResponse', DLNAServiceXMSMediaReceiverRegistrar::TYPE, array(
			'Result' => $this->_authorized ? 1 : 0
		));
	}
}