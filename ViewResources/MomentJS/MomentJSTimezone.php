<?php
namespace Quark\ViewResources\MomentJS;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceWithDependencies;
use Quark\IQuarkForeignViewResource;

use Quark\IQuarkViewResourceType;

use Quark\QuarkDTO;
use Quark\QuarkJSViewResourceType;

/**
 * Class MomentJSTimezone
 *
 * @package Quark\ViewResources\MomentJS
 */
class MomentJSTimezone implements IQuarkViewResource, IQuarkForeignViewResource, IQuarkViewResourceWithDependencies {
	const CURRENT_VERSION = '0.4.0';

	/**
	 * @var string $_version = self::CURRENT_VERSION
	 */
	private $_version = self::CURRENT_VERSION;

	/**
	 * @param string $version = self::CURRENT_VERSION
	 */
	public function __construct ($version = self::CURRENT_VERSION) {
		$this->_version = $version;
	}

	/**
	 * @return IQuarkViewResourceType
	 */
	public function Type () {
		return new QuarkJSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return 'https://cdn.rawgit.com/moment/moment-timezone/' . $this->_version . '/builds/moment-timezone-with-data.min.js';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			new MomentJS()
		);
	}
}