<?php
namespace Quark\Extensions\UPnP\Providers\DLNA\Services;

use Quark\Extensions\UPnP\IQuarkUPnPProviderService;
use Quark\Extensions\UPnP\UPnPServiceControlProtocol;
use Quark\Extensions\UPnP\UPnPServiceControlProtocolAction;
use Quark\Extensions\UPnP\UPnPServiceControlProtocolVariable;
use Quark\Extensions\UPnP\UPnPServiceControlProtocolVariableBehavior;
use Quark\Extensions\UPnP\UPnPServiceDescription;

/**
 * Class DLNAServiceXMSMediaReceiverRegistrar
 *
 * @package Quark\Extensions\UPnP\Providers\DLNA\Services
 */
class DLNAServiceXMSMediaReceiverRegistrar implements IQuarkUPnPProviderService {
	const NAME = 'X_MS_MediaReceiverRegistrar';
	const ID = 'urn:microsoft.com:serviceId:X_MS_MediaReceiverRegistrar';
	const TYPE = 'urn:microsoft.com:service:X_MS_MediaReceiverRegistrar:1';

	const VAR_AUTHORIZATION_GRANTED_UPDATE_ID = 'AuthorizationGrantedUpdateID';
	const VAR_AUTHORIZATION_DENIED_UPDATE_ID = 'AuthorizationDeniedUpdateID';
	const VAR_VALIDATION_SUCCEEDED_UPDATE_ID = 'ValidationSucceededUpdateID';
	const VAR_VALIDATION_REVOKED_UPDATE_ID = 'ValidationRevokedUpdateID';
	const VAR_A_ARG_TYPE_DEVICE_ID = 'A_ARG_TYPE_DeviceID';
	const VAR_A_ARG_TYPE_RESULT = 'A_ARG_TYPE_Result';
	const VAR_A_ARG_TYPE_REGISTRATION_REQ_MSG = 'A_ARG_TYPE_RegistrationReqMsg';
	const VAR_A_ARG_TYPE_REGISTRATION_RESP_MSG = 'A_ARG_TYPE_RegistrationRespMsg';

	use UPnPServiceControlProtocolVariableBehavior;

	/**
	 * DLNAServiceXMSMediaReceiverRegistrar constructor.
	 */
	public function __construct () {
		$this->UPnPServiceVariable(self::VAR_AUTHORIZATION_GRANTED_UPDATE_ID,	UPnPServiceControlProtocolVariable::DATA_TYPE_UNSIGNED_INT4, null, array(), true);
		$this->UPnPServiceVariable(self::VAR_AUTHORIZATION_DENIED_UPDATE_ID,	UPnPServiceControlProtocolVariable::DATA_TYPE_UNSIGNED_INT4, null, array(), true);
		$this->UPnPServiceVariable(self::VAR_VALIDATION_SUCCEEDED_UPDATE_ID,	UPnPServiceControlProtocolVariable::DATA_TYPE_UNSIGNED_INT4, null, array(), true);
		$this->UPnPServiceVariable(self::VAR_VALIDATION_REVOKED_UPDATE_ID,		UPnPServiceControlProtocolVariable::DATA_TYPE_UNSIGNED_INT4, null, array(), true);
		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_DEVICE_ID,				UPnPServiceControlProtocolVariable::DATA_TYPE_STRING);
		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_RESULT,					UPnPServiceControlProtocolVariable::DATA_TYPE_INT);
		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_REGISTRATION_REQ_MSG,	UPnPServiceControlProtocolVariable::DATA_TYPE_BIN_BASE64);
		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_REGISTRATION_RESP_MSG,	UPnPServiceControlProtocolVariable::DATA_TYPE_BIN_BASE64);
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

		$protocol->Action(UPnPServiceControlProtocolAction::WithArguments('IsAuthorized')
			->ArgumentIn('DeviceID',				$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_DEVICE_ID))
			->ArgumentOut('Result',					$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_RESULT))
		);

		$protocol->Action(UPnPServiceControlProtocolAction::WithArguments('RegisterDevice')
			->ArgumentIn('RegistrationReqMsg',		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_REGISTRATION_REQ_MSG))
			->ArgumentOut('RegistrationRespMsg',	$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_REGISTRATION_RESP_MSG))
		);

		$protocol->Action(UPnPServiceControlProtocolAction::WithArguments('IsValidated')
			->ArgumentIn('DeviceID',				$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_DEVICE_ID))
			->ArgumentOut('Result',					$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_RESULT))
		);

		return $protocol;
	}
}