<?php
namespace Quark\ViewResources\Socicon;

use Quark\IQuarkLocalViewResource;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkForeignViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\QuarkCSSViewResourceType;
use Quark\QuarkDTO;
use Quark\QuarkGenericViewResource;

/**
 * Class Socicon
 *
 * @package Quark\ViewResources\Socicon
 */
class Socicon implements IQuarkSpecifiedViewResource, IQuarkLocalViewResource, IQuarkViewResourceWithDependencies {
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
		return __DIR__ . '/Socicon.css';
		//return 'https://d1azc1qln24ryf.cloudfront.net/114779/Socicon/style-cf.css';
		//return 'https://s3.amazonaws.com/icomoon.io/114779/Socicon/style.css?u8vidh';
	}

	/**
	 * @return bool
	 */
	public function Minimize () {
		return true;
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
			QuarkGenericViewResource::CSS(__DIR__ . '/SociconColors.css')
		);
	}
}