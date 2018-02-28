<?php
namespace Quark\Extensions\UPnP;

use Quark\QuarkXMLNode;

/**
 * Class UPnPServiceControlProtocol
 *
 * @package Quark\Extensions\UPnP
 */
class UPnPServiceControlProtocol {
	const XMLNS = 'urn:schemas-upnp-org:service-1-0';

	const VERSION_MAJOR = 1;
	const VERSION_MINOR = 0;

	/**
	 * @var int $_versionMajor = self::VERSION_MAJOR
	 */
	private $_versionMajor = self::VERSION_MAJOR;

	/**
	 * @var int $_versionMinor = self::VERSION_MINOR
	 */
	private $_versionMinor = self::VERSION_MINOR;

	/**
	 * @var UPnPServiceControlProtocolAction[] $_actions = []
	 */
	private $_actions = array();

	/**
	 * @param int $version = self::VERSION_MAJOR
	 *
	 * @return int
	 */
	public function VersionMajor ($version = self::VERSION_MAJOR) {
		if (func_num_args() != 0)
			$this->_versionMajor = $version;

		return $this->_versionMajor;
	}

	/**
	 * @param int $version = self::VERSION_MINOR
	 *
	 * @return int
	 */
	public function VersionMinor ($version = self::VERSION_MINOR) {
		if (func_num_args() != 0)
			$this->_versionMinor = $version;

		return $this->_versionMinor;
	}

	/**
	 * @return UPnPServiceControlProtocolAction[]
	 */
	public function Actions () {
		return $this->_actions;
	}

	/**
	 * @param UPnPServiceControlProtocolAction $action = null
	 *
	 * @return UPnPServiceControlProtocol
	 */
	public function Action (UPnPServiceControlProtocolAction $action = null) {
		if ($action != null)
			$this->_actions[] = $action;

		return $this;
	}

	/**
	 * @return QuarkXMLNode
	 */
	public function ToXML () {
		$actions = array();
		$variables = array();

		foreach ($this->_actions as $i => &$action) {
			$actions[] = $action->ToXML();

			$actionVariables = $action->Variables();

			foreach ($actionVariables as $j => &$variable) {
				$name = $variable->Name();

				if (!isset($variables[$name]))
					$variables[$name] = $variable->ToXML();
			}
		}

		return QuarkXMLNode::Root(
			'scpd',
			array(
				'xmlns' => self::XMLNS
			),
			array(
				'specVersion' => array(
					'major' => 1,
					'minor' => 0
				),
				'actionList' => $actions,
				'serviceStateTable' => $variables
			)
		);
	}
}