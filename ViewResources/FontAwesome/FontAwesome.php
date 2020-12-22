<?php
namespace Quark\ViewResources\FontAwesome;

use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkForeignViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\QuarkDTO;
use Quark\QuarkCSSViewResourceType;
use Quark\QuarkGenericViewResource;

/**
 * Class FontAwesome
 *
 * @package Quark\ViewResources\FontAwesome
 */
class FontAwesome implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource, IQuarkViewResourceWithDependencies {
	const CURRENT_VERSION = '4.7.0';

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
		return new QuarkCSSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return 'https://maxcdn.bootstrapcdn.com/font-awesome/' . $this->_version . '/css/font-awesome.min.css';
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
			QuarkGenericViewResource::CSS(__DIR__ . '/FontAwesomeSocial.css')
		);
	}
}