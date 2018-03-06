<?php
namespace Quark\Extensions\UPnP\Providers\DLNA\ServiceControlDTO;

use Quark\QuarkDTO;

use Quark\Extensions\Quark\SOAP\SOAPEnvelope;
use Quark\Extensions\Quark\SOAP\SOAPElement;

use Quark\Extensions\UPnP\IQuarkUPnPProviderServiceControlDTO;
use Quark\Extensions\UPnP\Providers\DLNA\Services\DLNAServiceContentDirectory;

/**
 * Class DLNAServiceControlDTOGetSystemUpdateID
 *
 * @package Quark\Extensions\UPnP\Providers\DLNA\ServiceControlDTO
 */
class DLNAServiceControlDTOGetSystemUpdateID implements IQuarkUPnPProviderServiceControlDTO {
	/**
	 * @var string $_id = ''
	 */
	private $_id = '';

	/**
	 * @param string $id = ''
	 *
	 * @return string
	 */
	public function ID ($id = '') {
		if (func_num_args() != 0)
			$this->_id = $id;

		return $this->_id;
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

		$get = $body[0];
		if ($get->Name() != 'GetSystemUpdateID') return null;

		return $this;
	}

	/**
	 * @return SOAPElement[]
	 */
	public function UPnPProviderServiceControlResponse () {
		return new SOAPElement('u', 'GetSystemUpdateIDResponse', DLNAServiceContentDirectory::TYPE, array(
			'Id' => $this->_id
		));
	}
}