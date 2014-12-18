<?php
namespace Quark\ViewResources\FontAwesome;

use Quark\IQuarkViewResource;
use Quark\IQuarkForeignViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkCSSViewResourceType;
use Quark\QuarkDTO;

/**
 * Class FontAwesome
 *
 * @package Quark\ViewResources\FontAwesome
 */
class FontAwesome implements IQuarkViewResource, IQuarkForeignViewResource {
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
		return '//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}