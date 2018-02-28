<?php
namespace Quark\Extensions\UPnP\Providers\DLNA;

use Quark\QuarkXMLNode;

use Quark\Extensions\UPnP\UPnPProperty;

/**
 * Interface IQuarkDLNAElementResource
 *
 * @package Quark\Extensions\UPnP\Providers\DLNA
 */
interface IQuarkDLNAElementResource {
	/**
	 * @return QuarkXMLNode
	 */
	public function DLNAElementResource();

	/**
	 * @return UPnPProperty[]
	 */
	public function DLNAElementResourceUPnPProperties();
}