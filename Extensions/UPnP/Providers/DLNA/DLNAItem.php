<?php
namespace Quark\Extensions\UPnP\Providers\DLNA;

use Quark\IQuarkCollectionWithArrayAccess;

use Quark\QuarkCollectionBehaviorWithArrayAccess;
use Quark\QuarkXMLIOProcessor;
use Quark\QuarkXMLNode;

/**
 * Class DLNAItem
 *
 * @package Quark\Extensions\UPnP\Providers\DLNA
 */
class DLNAItem implements IQuarkCollectionWithArrayAccess {
	use QuarkCollectionBehaviorWithArrayAccess;

	/**
	 * @var QuarkXMLIOProcessor $_processor
	 */
	private $_processor;

	/**
	 * DigitalItem constructor.
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
	 * @param bool $escape = false
	 *
	 * @return string
	 */
	public function ToXML ($escape = false) {
		$elements = array();

		foreach ($this->_collection as $element)
			if ($element instanceof IQuarkDLNAElement)
				$elements[] = $element->DLNAElement();

		$out = $this->_processor->Encode($elements, false);

		if ($escape)
			$out = str_replace('<', '&lt;', str_replace('>', '&gt;', $out));

		return $out;
	}
}