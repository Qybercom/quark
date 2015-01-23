<?php
namespace Quark\DataProviders;

use Quark\IQuarkModel;

/**
 * Interface IQuarkRESTProvider
 *
 * @package Quark\DataProviders
 */
interface IQuarkRESTProvider {
	/**
	 * @param IQuarkModel $model
	 *
	 * @return string
	 */
	function Id(IQuarkModel $model);
}