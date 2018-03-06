<?php
namespace Quark\Extensions\UPnP\Providers\DLNA\Services;

use Quark\Extensions\UPnP\IQuarkUPnPProviderService;

use Quark\Extensions\UPnP\UPnPServiceControlProtocol;
use Quark\Extensions\UPnP\UPnPServiceControlProtocolAction;
use Quark\Extensions\UPnP\UPnPServiceControlProtocolVariable;
use Quark\Extensions\UPnP\UPnPServiceControlProtocolVariableBehavior;
use Quark\Extensions\UPnP\UPnPServiceDescription;

/**
 * Class DLNAServiceContentDirectory
 *
 * @package Quark\Extensions\UPnP\Providers\DLNA\Services
 */
class DLNAServiceContentDirectory implements IQuarkUPnPProviderService {
	const NAME = 'ContentDirectory';
	const ID = 'urn:upnp-org:serviceId:ContentDirectory';
	const TYPE = 'urn:schemas-upnp-org:service:ContentDirectory:1';

	const VAR_SYSTEM_UPDATE_ID = 'SystemUpdateID';
	const VAR_SEARCH_CAPABILITIES = 'SearchCapabilities';
	const VAR_SORT_CAPABILITIES = 'SortCapabilities';
	const VAR_A_ARG_TYPE_UPDATE_ID = 'A_ARG_TYPE_UpdateID';
	const VAR_A_ARG_TYPE_OBJECT_ID = 'A_ARG_TYPE_ObjectID';
	const VAR_A_ARG_TYPE_SEARCH_CRITERIA = 'A_ARG_TYPE_SearchCriteria';
	const VAR_A_ARG_TYPE_SORT_CRITERIA = 'A_ARG_TYPE_SortCriteria';
	const VAR_A_ARG_TYPE_FILTER = 'A_ARG_TYPE_Filter';
	const VAR_A_ARG_TYPE_RESULT = 'A_ARG_TYPE_Result';
	const VAR_A_ARG_TYPE_INDEX = 'A_ARG_TYPE_Index';
	const VAR_A_ARG_TYPE_COUNT = 'A_ARG_TYPE_Count';
	const VAR_A_ARG_TYPE_BROWSE_FLAG = 'A_ARG_TYPE_BrowseFlag';
	const VAR_A_ARG_TYPE_BROWSE_LETTER = 'A_ARG_TYPE_BrowseLetter';
	const VAR_A_ARG_TYPE_CATEGORY_TYPE = 'A_ARG_TYPE_CategoryType';
	const VAR_A_ARG_TYPE_RID = 'A_ARG_TYPE_RID';
	const VAR_A_ARG_TYPE_POS_SEC = 'A_ARG_TYPE_PosSec';
	const VAR_A_ARG_TYPE_FEATURE_LIST = 'A_ARG_TYPE_Featurelist';

	const VALUE_BROWSE_METADATA = 'BrowseMetadata';
	const VALUE_BROWSE_DIRECT_CHILDREN = 'BrowseDirectChildren';

	use UPnPServiceControlProtocolVariableBehavior;

	/**
	 * DLNAServiceContentDirectory constructor.
	 */
	public function __construct () {
		$this->UPnPServiceVariable(self::VAR_SYSTEM_UPDATE_ID,				UPnPServiceControlProtocolVariable::DATA_TYPE_UNSIGNED_INT4, null, array(), true);
		$this->UPnPServiceVariable(self::VAR_SEARCH_CAPABILITIES,			UPnPServiceControlProtocolVariable::DATA_TYPE_STRING);
		$this->UPnPServiceVariable(self::VAR_SORT_CAPABILITIES,				UPnPServiceControlProtocolVariable::DATA_TYPE_STRING);
		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_UPDATE_ID,			UPnPServiceControlProtocolVariable::DATA_TYPE_UNSIGNED_INT4);
		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_OBJECT_ID,			UPnPServiceControlProtocolVariable::DATA_TYPE_STRING);
		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_SEARCH_CRITERIA,	UPnPServiceControlProtocolVariable::DATA_TYPE_STRING);
		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_SORT_CRITERIA,		UPnPServiceControlProtocolVariable::DATA_TYPE_STRING);
		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_FILTER,				UPnPServiceControlProtocolVariable::DATA_TYPE_STRING);
		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_RESULT,				UPnPServiceControlProtocolVariable::DATA_TYPE_STRING);
		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_INDEX,				UPnPServiceControlProtocolVariable::DATA_TYPE_STRING);
		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_COUNT,				UPnPServiceControlProtocolVariable::DATA_TYPE_UNSIGNED_INT4);
		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_CATEGORY_TYPE,		UPnPServiceControlProtocolVariable::DATA_TYPE_UNSIGNED_INT4, '');
		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_RID,				UPnPServiceControlProtocolVariable::DATA_TYPE_UNSIGNED_INT4, '');
		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_POS_SEC,			UPnPServiceControlProtocolVariable::DATA_TYPE_UNSIGNED_INT4, '');
		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_FEATURE_LIST,		UPnPServiceControlProtocolVariable::DATA_TYPE_STRING, '');
		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_BROWSE_LETTER,		UPnPServiceControlProtocolVariable::DATA_TYPE_STRING);
		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_BROWSE_FLAG,		UPnPServiceControlProtocolVariable::DATA_TYPE_STRING, null, array(
			self::VALUE_BROWSE_METADATA,
			self::VALUE_BROWSE_DIRECT_CHILDREN
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

		$protocol->Action(UPnPServiceControlProtocolAction::WithArguments('GetSystemUpdateID')
			->ArgumentOut('Id', $this->UPnPServiceVariable(self::VAR_SYSTEM_UPDATE_ID))
		);

		$protocol->Action(UPnPServiceControlProtocolAction::WithArguments('GetSearchCapabilities')
			->ArgumentOut('SearchCaps', $this->UPnPServiceVariable(self::VAR_SEARCH_CAPABILITIES))
		);

		$protocol->Action(UPnPServiceControlProtocolAction::WithArguments('GetSortCapabilities')
			->ArgumentOut('SortCaps', $this->UPnPServiceVariable(self::VAR_SORT_CAPABILITIES))
		);

		$protocol->Action(UPnPServiceControlProtocolAction::WithArguments('Browse')
			->ArgumentIn('ObjectID',		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_OBJECT_ID))
			->ArgumentIn('BrowseFlag',		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_BROWSE_FLAG))
			->ArgumentIn('Filter',			$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_FILTER))
			->ArgumentIn('StartingIndex',	$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_INDEX))
			->ArgumentIn('RequestedCount',	$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_COUNT))
			->ArgumentIn('SortCriteria',	$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_SORT_CRITERIA))
			->ArgumentOut('Result',			$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_RESULT))
			->ArgumentOut('NumberReturned',	$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_COUNT))
			->ArgumentOut('TotalMatches',	$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_COUNT))
			->ArgumentOut('UpdateID',		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_UPDATE_ID))
		);

		$protocol->Action(UPnPServiceControlProtocolAction::WithArguments('Search')
			->ArgumentIn('ContainerID',		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_OBJECT_ID))
			->ArgumentIn('SearchCriteria',	$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_SEARCH_CRITERIA))
			->ArgumentIn('Filter',			$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_FILTER))
			->ArgumentIn('StartingIndex',	$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_INDEX))
			->ArgumentIn('RequestedCount',	$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_COUNT))
			->ArgumentIn('SortCriteria',	$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_SORT_CRITERIA))
			->ArgumentOut('Result',			$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_RESULT))
			->ArgumentOut('NumberReturned',	$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_COUNT))
			->ArgumentOut('TotalMatches',	$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_COUNT))
			->ArgumentOut('UpdateID',		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_UPDATE_ID))
		);

		$protocol->Action(UPnPServiceControlProtocolAction::WithArguments('X_SetBookmark')
			->ArgumentIn('CategoryType',	$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_CATEGORY_TYPE))
			->ArgumentIn('RID',				$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_RID))
			->ArgumentIn('ObjectID',		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_OBJECT_ID))
			->ArgumentIn('PosSecond',		$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_POS_SEC))
		);

		$protocol->Action(UPnPServiceControlProtocolAction::WithArguments('X_GetFeatureList')
			->ArgumentOut('FeatureList',	$this->UPnPServiceVariable(self::VAR_A_ARG_TYPE_FEATURE_LIST))
		);

		return $protocol;
	}
}