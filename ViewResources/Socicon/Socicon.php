<?php
namespace Quark\ViewResources\Socicon;

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
class Socicon implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource, IQuarkViewResourceWithDependencies {
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
		return 'https://d1azc1qln24ryf.cloudfront.net/114779/Socicon/style-cf.css';
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