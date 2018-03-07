<?php
namespace Quark\Extensions\UPnP\Providers\DLNA\Services;

use Quark\Extensions\UPnP\IQuarkUPnPProviderService;

use Quark\Extensions\UPnP\UPnPServiceControlProtocol;
use Quark\Extensions\UPnP\UPnPServiceControlProtocolAction;
use Quark\Extensions\UPnP\UPnPServiceControlProtocolVariable;
use Quark\Extensions\UPnP\UPnPServiceControlProtocolVariableBehavior;
use Quark\Extensions\UPnP\UPnPServiceDescription;

/**
 * Class DLNAServiceConnectionManager
 *
 * @package Quark\Extensions\UPnP\Providers\DLNA\Services
 */
class DLNAServiceConnectionManager implements IQuarkUPnPProviderService {
	const NAME = 'ConnectionManager';
	const ID = 'urn:upnp-org:serviceId:ConnectionManager';
	const TYPE = 'urn:schemas-upnp-org:service:ConnectionManager:1';

	const VAR_SINK_PROTOCOL_INFO = 'SinkProtocolInfo';
	const VAR_SOURCE_PROTOCOL_INFO = 'SourceProtocolInfo';
	const VAR_CURRENT_CONNECTION_IDS = 'CurrentConnectionIDs';
	const VAR_A_ARG_TYPE_PROTOCOL_INFO = 'A_ARG_TYPE_ProtocolInfo';
	const VAR_A_ARG_TYPE_CONNECTION_ID = 'A_ARG_TYPE_ConnectionID';
	const VAR_A_ARG_TYPE_CONNECTION_STATUS = 'A_ARG_TYPE_ConnectionStatus';
	const VAR_A_ARG_TYPE_CONNECTION_MANAGER = 'A_ARG_TYPE_ConnectionManager';
	const VAR_A_ARG_TYPE_AV_TRANSPORT_ID = 'A_ARG_TYPE_AVTransportID';
	const VAR_A_ARG_TYPE_RCS_ID = 'A_ARG_TYPE_RcsID';
	const VAR_A_ARG_TYPE_DIRECTION = 'A_ARG_TYPE_Direction';

	const VALUE_CONNECTION_STATUS_OK = 'OK';
	const VALUE_CONNECTION_STATUS_CONTENT_FORMAT_MISMATCH = 'ContentFormatMismatch';
	const VALUE_CONNECTION_STATUS_INSUFFICIENT_BANDWIDTH = 'InsufficientBandwidth';
	const VALUE_CONNECTION_STATUS_UNRELIABLE_CHANNEL = 'UnreliableChannel';
	const VALUE_CONNECTION_STATUS_UNKNOWN = 'Unknown';
	const VALUE_DIRECTION_INPUT = 'Input';
	const VALUE_DIRECTION_OUTPUT = 'Output';

	use UPnPServiceControlProtocolVariableBehavior;

	/**
	 * DLNAServiceConnectionManager constructor.
	 */
	public function __construct () {
		$this->UPnPServiceVariable(self::VAR_SINK_PROTOCOL_INFO,			UPnPServiceControlProtocolVariable::DATA_TYPE_STRING, null, array(), true);
		$this->UPnPServiceVariable(self::VAR_SOURCE_PROTOCOL_INFO,			UPnPServiceControlProtocolVariable::DATA_TYPE_STRING, null, array(), true);
		$this->UPnPServiceVariable(self::VAR_CURRENT_CONNECTION_IDS,		UPnPServiceControlProtocolVariable::DATA_TYPE_STRING, null, array(), true);
		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_PROTOCOL_INFO,		UPnPServiceControlProtocolVariable::DATA_TYPE_STRING);
		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_CONNECTION_ID,		UPnPServiceControlProtocolVariable::DATA_TYPE_INT4);
		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_CONNECTION_STATUS,	UPnPServiceControlProtocolVariable::DATA_TYPE_STRING, null, array(
			self::VALUE_CONNECTION_STATUS_OK,
			self::VALUE_CONNECTION_STATUS_CONTENT_FORMAT_MISMATCH,
			self::VALUE_CONNECTION_STATUS_INSUFFICIENT_BANDWIDTH,
			self::VALUE_CONNECTION_STATUS_UNRELIABLE_CHANNEL,
			self::VALUE_CONNECTION_STATUS_UNKNOWN
		));
		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_CONNECTION_MANAGER,	UPnPServiceControlProtocolVariable::DATA_TYPE_STRING);
		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_AV_TRANSPORT_ID,	UPnPServiceControlProtocolVariable::DATA_TYPE_INT4);
		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_RCS_ID,				UPnPServiceControlProtocolVariable::DATA_TYPE_INT4);
		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_DIRECTION,			UPnPServiceControlProtocolVariable::DATA_TYPE_STRING, null, array(
			self::VALUE_DIRECTION_INPUT,
			self::VALUE_DIRECTION_OUTPUT
		));
	}

	/**
	 * @return string
	 */
	public function UPnPServiceName () {
		return self::NAME;
	}

	/**
	 * @return UPnPServiceDescription
	 */
	public function UPnPServiceDescription () {
		return new UPnPServiceDescription(
			self::ID,
			self::TYPE,
			$this->_urlDescription . self::NAME,
			$this->_urlControl . self::NAME,
			$this->_urlEvent . self::NAME
		);
	}

	/**
	 * @return UPnPServiceControlProtocol
	 */
	public function UPnPServiceControlProtocol () {
		$protocol = new UPnPServiceControlProtocol();

		$protocol->Action(UPnPServiceControlProtocolAction::WithArguments('GetProtocolInfo')
			->ArgumentOut('Source',					$this->UPnPServiceVariable(self::VAR_SINK_PROTOCOL_INFO))
			->ArgumentOut('Sink',					$this->UPnPServiceVariable(self::VAR_SINK_PROTOCOL_INFO))
		);

		$protocol->Action(UPnPServiceControlProtocolAction::WithArguments('GetCurrentConnectionInfo')
			->ArgumentIn('ConnectionID',			$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_CONNECTION_ID))
			->ArgumentOut('RcsID',					$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_RCS_ID))
			->ArgumentOut('AVTransportID',			$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_AV_TRANSPORT_ID))
			->ArgumentOut('ProtocolInfo',			$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_PROTOCOL_INFO))
			->ArgumentOut('PeerConnectionManager',	$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_CONNECTION_MANAGER))
			->ArgumentOut('PeerConnectionID',		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_CONNECTION_ID))
			->ArgumentOut('Direction',				$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_DIRECTION))
			->ArgumentOut('Status',					$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_CONNECTION_STATUS))
		);

		$protocol->Action(UPnPServiceControlProtocolAction::WithArguments('GetCurrentConnectionIDs')
			->ArgumentOut('ConnectionIDs',			$this->UPnPServiceVariable(self::VAR_CURRENT_CONNECTION_IDS))
		);

		return $protocol;
	}
}