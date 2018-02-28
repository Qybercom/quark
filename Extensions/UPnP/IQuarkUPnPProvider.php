<?php
namespace Quark\Extensions\UPnP;

use Quark\QuarkKeyValuePair;

/**
 * Interface IQuarkUPnPProvider
 *
 * @package Quark\Extensions\UPnP
 */
interface IQuarkUPnPProvider {
	/**
	 * @param object $ini
	 *
	 * @return mixed
	 */
	public function UPnPProviderOptions($ini);

	/**
	 * @return string
	 */
	public function UPnPProviderDeviceType();

	/**
	 * @return QuarkKeyValuePair[]
	 */
	public function UPnPProviderAttributes();

	/**
	 * @return QuarkKeyValuePair[]
	 */
	public function UPnPProviderAttributesXMLNS();

	/**
	 * @return IQuarkUPnPProviderService[]
	 */
	public function UPnPProviderServices();
}