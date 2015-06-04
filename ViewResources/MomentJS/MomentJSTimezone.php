<?php
namespace Quark\ViewResources\MomentJS;

use Quark\IQuarkForeignViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\IQuarkViewResourceType;

use Quark\QuarkDTO;
use Quark\QuarkJSViewResourceType;

/**
 * Class MomentJSTimezone
 *
 * @package Quark\ViewResources\MomentJS
 */
class MomentJSTimezone implements IQuarkViewResource, IQuarkForeignViewResource, IQuarkViewResourceWithDependencies {
	private $_version = '';

	/**
	 * @param string $version = '0.3.1'
	 */
	public function __construct ($version = '0.3.1') {
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
		return 'https://cdnjs.cloudflare.com/ajax/libs/moment-timezone/' . $this->_version . '/moment-timezone.min.js';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}

	/**
	 * @return array
	 */
	public function Dependencies () {
		return array(
			new MomentJSTimezoneData()
		);
	}
}