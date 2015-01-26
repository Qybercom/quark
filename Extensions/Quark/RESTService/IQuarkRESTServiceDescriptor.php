<?php
namespace Quark\Extensions\Quark\RESTService;

use Quark\IQuarkModel;

/**
 * Interface IQuarkRESTServiceDescriptor
 *
 * @package Quark\Extensions\Quark\RESTService
 */
interface IQuarkRESTServiceDescriptor {
	/**
	 * @param IQuarkModel $model
	 *
	 * @return string
	 */
	function IdentifyModel(IQuarkModel $model);
}