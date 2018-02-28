<?php
namespace Quark\Extensions\UPnP\Providers\DLNA;

use Quark\QuarkXMLNode;

/**
 * Interface IQuarkDLNAElement
 *
 * @package Quark\Extensions\UPnP\Providers\DLNA
 */
interface IQuarkDLNAElement {
	/**
	 * @return QuarkXMLNode
	 */
	public function DLNAElement();
}