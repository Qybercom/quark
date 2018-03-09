<?php
namespace Quark\Extensions\UPnP\Providers\DLNA\ServiceControlDTO;

use Quark\QuarkCollection;
use Quark\QuarkDTO;
use Quark\QuarkModel;
use Quark\QuarkXMLIOProcessor;
use Quark\QuarkXMLNode;

use Quark\Extensions\Quark\SOAP\SOAPEnvelope;
use Quark\Extensions\Quark\SOAP\SOAPElement;

use Quark\Extensions\UPnP\IQuarkUPnPProviderServiceControlDTO;
use Quark\Extensions\UPnP\Providers\DLNA\DLNAElement;
use Quark\Extensions\UPnP\Providers\DLNA\Services\DLNAServiceContentDirectory;

/**
 * Class DLNAServiceControlDTOBrowse
 *
 * @package Quark\Extensions\UPnP\Providers\DLNA\ServiceControlDTO
 */
class DLNAServiceControlDTOBrowse implements IQuarkUPnPProviderServiceControlDTO {
	const FILTER_ALL = '*';

	/**
	 * @var string $_objectID = DLNAElement::ELEMENT_CONTAINER_ROOT
	 */
	private $_objectID = DLNAElement::ELEMENT_CONTAINER_ROOT;

	/**
	 * @var string $_browseFlag = ''
	 */
	private $_browseFlag = '';

	/**
	 * @var string $_filter = self::FILTER_ALL
	 */
	private $_filter = self::FILTER_ALL;

	/**
	 * @var string $_startingIndex = '0'
	 */
	private $_startingIndex = '0';

	/**
	 * @var string $_sortCriteria = ''
	 */
	private $_sortCriteria = '';

	/**
	 * @var int $_requestedCount = 0
	 */
	private $_requestedCount = 0;

	/**
	 * @var QuarkCollection|DLNAElement[] $_elements
	 */
	private $_elements;

	/**
	 * @var string $_updateID = null
	 */
	private $_updateID = null;

	/**
	 * @var QuarkXMLIOProcessor $_processor
	 */
	private $_processor;

	/**
	 * DLNAServiceControlDTOBrowse constructor.
	 */
	public function __construct () {
		$this->_processor = new QuarkXMLIOProcessor(QuarkXMLNode::Root(
			'DIDL-Lite',
			array(
				'xmlns' => 'urn:schemas-upnp-org:metadata-1-0/DIDL-Lite/',
				'xmlns:dc' => 'http://purl.org/dc/elements/1.1/',
				'xmlns:dlna' => 'urn:schemas-dlna-org:metadata-1-0/',
				'xmlns:upnp' => 'urn:schemas-upnp-org:metadata-1-0/upnp/'
			)
		));
	}

	/**
	 * @param string $id = DLNAElement::ELEMENT_CONTAINER_ROOT
	 *
	 * @return string
	 */
	public function ObjectID ($id = DLNAElement::ELEMENT_CONTAINER_ROOT) {
		if (func_num_args() != 0)
			$this->_objectID = $id;

		return $this->_objectID;
	}

	/**
	 * @param string $flag = ''
	 *
	 * @return string
	 */
	public function BrowseFlag ($flag = '') {
		if (func_num_args() != 0)
			$this->_browseFlag = $flag;

		return $this->_browseFlag;
	}

	/**
	 * @param string $filter = ''
	 *
	 * @return string
	 */
	public function Filter ($filter = '') {
		if (func_num_args() != 0)
			$this->_filter = $filter;

		return $this->_filter;
	}

	/**
	 * @param string $index = '0'
	 *
	 * @return string
	 */
	public function StartingIndex ($index = '0') {
		if (func_num_args() != 0)
			$this->_startingIndex = $index;

		return $this->_startingIndex;
	}

	/**
	 * @param int $count = 0
	 *
	 * @return int
	 */
	public function RequestedCount ($count = 0) {
		if (func_num_args() != 0)
			$this->_requestedCount = $count;

		return $this->_requestedCount;
	}

	/**
	 * @param string $sort = ''
	 *
	 * @return string
	 */
	public function SortCriteria ($sort = '') {
		if (func_num_args() != 0)
			$this->_sortCriteria = $sort;

		return $this->_sortCriteria;
	}

	/**
	 * @param QuarkCollection|DLNAElement[] $elements = null
	 *
	 * @return QuarkCollection
	 */
	public function &Elements (QuarkCollection $elements = null) {
		if (func_num_args() != 0)
			$this->_elements = $elements;

		return $this->_elements;
	}

	/**
	 * @param string $id = null
	 *
	 * @return string
	 */
	public function UpdateID ($id = null) {
		if (func_num_args() != 0)
			$this->_updateID = $id;

		return $this->_updateID;
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

		$browse = $body[0];
		if ($browse->Name() != 'Browse') return null;

		$data = $browse->Data();
		$params = array(
			'ObjectID',
			'BrowseFlag',
			'Filter',
			'StartingIndex',
			'RequestedCount',
			'SortCriteria'
		);

		foreach ($params as $i => &$param) {
			if (!isset($data->$param)) continue;

			$value = $data->$param;
			$this->$param($value instanceof QuarkXMLNode ? $value->Data() : $value);
		}

		return $this;
	}

	/**
	 * @return SOAPElement[]
	 */
	public function UPnPProviderServiceControlResponse () {
		$elements = array();

		foreach ($this->_elements as $element)
			if ($element instanceof QuarkModel && $element->Model() instanceof DLNAElement)
				$elements[] = $element->ToXML();

		$data = array(
			'Result' => str_replace('<', '&lt;', str_replace('>', '&gt;', $this->_processor->Encode($elements, false))),
			'NumberReturned' => sizeof($this->_elements),
			'TotalMatches' => $this->_elements->CountAll()
		);

		if ($this->_updateID !== null)
			$data['UpdateID'] = $this->_updateID;

		return new SOAPElement('u', 'BrowseResponse', DLNAServiceContentDirectory::TYPE, $data);
	}
}