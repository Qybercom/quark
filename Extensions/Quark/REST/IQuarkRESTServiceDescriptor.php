<?php
namespace Quark\Extensions\Quark\REST;

use Quark\IQuarkModel;

/**
 * Interface IQuarkRESTServiceDescriptor
 *
 * @package Quark\Extensions\Quark\REST
 */
interface IQuarkRESTServiceDescriptor {
	/**
	 * @param IQuarkModel $model
	 *
	 * @return string
	 */
	function IdentifyModel(IQuarkModel $model);
}