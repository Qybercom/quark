<?php
namespace Quark\DataProviders;

use Quark\IQuarkDataProvider;
use Quark\IQuarkModel;

use Quark\QuarkCredentials;

use Quark\QuarkArchException;

/**
 * Class PostgreSQL
 *
 * @package Quark\DataProviders
 */
class PostgreSQL implements IQuarkDataProvider {
	private static $_pool = array();

	/**
	 * @return array
	 */
	public static function SourcePool () {
		return self::$_pool;
	}

	/**
	 * @param $name
	 *
	 * @return IQuarkDataProvider
	 * @throws QuarkArchException
	 */
	public static function SourceGet ($name) {
		if (!isset(self::$_pool[$name]))
			throw new QuarkArchException('PostgreSQL connection \'' . $name . '\' is not pooled');

		return self::$_pool[$name];
	}

	/**
	 * @param                  $name
	 * @param QuarkCredentials $credentials
	 */
	public static function SourceSet ($name, QuarkCredentials $credentials) {
		self::$_pool[$name] = new Mongo();
		self::$_pool[$name]->Connect($credentials);
	}

	/**
	 * @param                  $name
	 * @param QuarkCredentials $credentials
	 */
	public function Source ($name, QuarkCredentials $credentials) {
		$this->Connect($credentials);
		self::$_pool[$name] = $this;
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return mixed
	 */
	public function Create (IQuarkModel $model) {
		// TODO: Implement Create() method.
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return mixed
	 */
	public function Save (IQuarkModel $model) {
		// TODO: Implement Save() method.
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return mixed
	 */
	public function Remove (IQuarkModel $model) {
		// TODO: Implement Remove() method.
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 *
	 * @return array
	 */
	public function Find (IQuarkModel $model, $criteria) {
		// TODO: Implement Find() method.
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 *
	 * @return IQuarkModel
	 */
	public function FindOne (IQuarkModel $model, $criteria) {
		// TODO: Implement FindOne() method.
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $id
	 *
	 * @return IQuarkModel
	 */
	public function FindOneById (IQuarkModel $model, $id) {
		// TODO: Implement FindOneById() method.
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param             $options
	 *
	 * @return mixed
	 */
	public function Update (IQuarkModel $model, $criteria, $options) {
		// TODO: Implement Update() method.
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param             $options
	 *
	 * @return mixed
	 */
	public function Delete (IQuarkModel $model, $criteria, $options) {
		// TODO: Implement Delete() method.
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param             $limit
	 * @param             $skip
	 *
	 * @return int
	 */
	public function Count (IQuarkModel $model, $criteria, $limit, $skip) {
		// TODO: Implement Count() method.
	}
}