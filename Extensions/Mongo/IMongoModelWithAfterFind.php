<?php
namespace Quark\Extensions\Mongo;

/**
 * Interface IMongoModelWithAfterFind
 * @package Quark\Extensions\Mongo
 */
interface IMongoModelWithAfterFind extends IMongoModel {
	/**
	 * @param $item
	 * @return mixed
	 */
	function AfterFind($item);
}