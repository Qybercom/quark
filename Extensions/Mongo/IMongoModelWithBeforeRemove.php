<?php
namespace Quark\Extensions\Mongo;

/**
 * Interface IMongoModelWithBeforeRemove
 * @package Quark\Extensions\Mongo
 */
interface IMongoModelWithBeforeRemove {
	/**
	 * @return bool|null
	 */
	function BeforeRemove();
}