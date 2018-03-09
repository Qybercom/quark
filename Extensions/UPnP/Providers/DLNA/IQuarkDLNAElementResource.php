<?php
namespace Quark\Extensions\UPnP\Providers\DLNA;

use Quark\QuarkCollection;
use Quark\QuarkKeyValuePair;

/**
 * Interface IQuarkDLNAElementResource
 *
 * @package Quark\Extensions\UPnP\Providers\DLNA
 */
interface IQuarkDLNAElementResource {
	/**
	 * @return string
	 */
	public function DLNAElementResourceURL();

	/**
	 * @return QuarkKeyValuePair[]
	 */
	public function DLNAElementResourceAttributes();

	/**
	 * @return QuarkCollection|DLNAElementProperty[]
	 */
	public function DLNAElementResourceItemProperties();
}