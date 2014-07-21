<?php
namespace Quark\Extensions\Mongo;

/**
 * Interface IMongoModel
 * @package Quark\Extensions\Mongo
 */
interface IMongoModel {
	/**
	 * @return string
	 */
	static function Storage();

	/**
	 * @return mixed
	 */
	function Fields();

	/**
	 * @return mixed
	 */
	function Rules();
}