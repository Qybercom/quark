<?php
namespace Quark\Extensions\Mongo;

use Quark\IQuarkExtension;
use Quark\Quark;
use Quark\QuarkArchException;
use Quark\QuarkConnectionException;

/**
 * Class Source
 * @package Quark\Extensions\Mongo
 */
class Source implements IQuarkExtension {
	private static $_pool = array();

	/**
	 * @param null|Config $config
	 * @throws QuarkConnectionException
	 * @return mixed|void
	 */
	public static function Config ($config) {
		$pool = $config->Pool();
		$db = null;

		/**
		 * @var \Quark\QuarkCredentials $connection
		 */
		foreach ($pool as $key => $connection) {
			if ($connection->Used($pool)) continue;

			$db = $connection->suffix;

			try {
				self::$_pool[$key] = @new \MongoClient($connection->uri());
				self::$_pool[$key] = self::$_pool[$key]->$db;
			}
			catch (\Exception $e) {
				throw new QuarkConnectionException($connection, Quark::LOG_FATAL);
			}
		}
	}

	/**
	 * @param $key
	 * @return mixed
	 * @throws QuarkArchException
	 */
	public static function Get ($key) {
		if (!isset(self::$_pool[$key]))
			throw new QuarkArchException('Undefined model source ' . $key);

		return self::$_pool[$key];
	}

	/**
	 * @TODO: Implement methods for opening and closing specified connections
	 */
}