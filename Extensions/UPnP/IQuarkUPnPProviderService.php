<?php
namespace Quark\Extensions\UPnP;

/**
 * Interface IQuarkUPnPProviderService
 *
 * @package Quark\Extensions\UPnP
 */
interface IQuarkUPnPProviderService {
	/**
	 * @return string
	 */
	public function UPnPServiceName();

	/**
	 * @param string $description
	 * @param string $control
	 * @param string $event
	 *
	 * @return mixed
	 */
	public function UPnPServiceURLs($description, $control, $event);

	/**
	 * @param string $name
	 *
	 * @return UPnPServiceControlProtocolVariable
	 */
	public function UPnPServiceVariable($name);

	/**
	 * @return UPnPServiceControlProtocolVariable[]
	 */
	public function UPnPServiceVariableList();

	/**
	 * @return UPnPServiceDescription
	 */
	public function UPnPServiceDescription();

	/**
	 * @return UPnPServiceControlProtocol
	 */
	public function UPnPServiceControlProtocol();
}