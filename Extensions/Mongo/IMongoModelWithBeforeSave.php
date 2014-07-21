<?php
namespace Quark\Extensions\Mongo;

/**
 * Interface IMongoModelWithBeforeSave
 * @package Quark\Extensions\Mongo
 */
interface IMongoModelWithBeforeSave {
	/**
	 * @return bool|null
	 */
	function BeforeSave();
}