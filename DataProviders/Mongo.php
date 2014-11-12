<?php
namespace Quark\DataProviders;

use Quark\IQuarkDataProvider;
use Quark\IQuarkModel;

use Quark\Quark;
use Quark\QuarkArchException;
use Quark\QuarkCredentials;
use Quark\QuarkConnectionException;

/**
 * Class Database
 *
 * @package Quark\DataProviders
 */
class Mongo implements IQuarkDataProvider {
	private $_connection;
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
	 * @return QuarkCredentials
	 * @throws QuarkArchException
	 */
	public static function SourceGet ($name) {
		if (!isset(self::$_pool[$name]))
			throw new QuarkArchException('MongoDB connection \'' . $name . '\' is not pooled');

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
	 * @param QuarkCredentials $credentials
	 *
	 * @return mixed|void
	 * @throws QuarkConnectionException
	 */
	public function Connect (QuarkCredentials $credentials) {
		try {
			$options = array();

			if (is_array($credentials->Options()))
				$options = $credentials->Options();

			$this->_connection = new \MongoClient($credentials->uri(), $options);

			if ($credentials->suffix) {
				$db = $credentials->suffix;
				$this->_connection = $this->_connection->$db;
			}
		}
		catch (\Exception $e) {
			throw new QuarkConnectionException($credentials, Quark::LOG_FATAL);
		}
	}

	private function _collection ($model ,$options) {
		$collection = isset($options['collection'])
			? $options['collection']
			: Quark::ClassName($model);

		return  $this->_connection->$collection;
	}

	/**
	 * @param IQuarkModel $model
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Create (IQuarkModel $model, $options = []) {
		return $this->_collection($model, $options)->insert($model, $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Save (IQuarkModel $model, $options = []) {
		return $this->_collection($model, $options)->save($model, $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Remove (IQuarkModel $model, $options = []) {
		if (!isset($model->_id)) return false;

		return $this->_collection($model, $options)->remove(array(
			'_id' => new \MongoId($model->_id)
		), $options);
	}

	/**
	 * @param $options
	 *
	 * @return array
	 */
	private static function _fields ($options) {
		return isset($options['fields']) && is_array($options['fields'])
			? $options['fields']
			: array();
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return array
	 */
	public function Find (IQuarkModel $model, $criteria, $options = []) {
		$raw = $this->_collection($model, $options)->find($criteria, self::_fields($options));

		if (isset($options['sort']))
			$raw->sort($options['sort']);

		if (isset($options['limit']))
			$raw->limit($options['limit']);

		if (isset($options['skip']))
			$raw->skip($options['skip']);

		if (isset($options['getId']) && $options['getId'] == true) {
			$buffer = array();
			$item = null;

			foreach ($raw as $i => $document) {
				$item = $document;
				$item->_id = $document->_id->{'$id'};

				$buffer[] = $item;
			}

			$raw = $buffer;
		}

		return $raw;
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return IQuarkModel
	 */
	public function FindOne (IQuarkModel $model, $criteria, $options = []) {
		return $this->_collection($model, $options)->findOne($criteria, self::_fields($options)/*, $options*/);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $id
	 * @param $options
	 *
	 * @return IQuarkModel
	 */
	public function FindOneById (IQuarkModel $model, $id, $options = []) {
		return $this->_collection($model, $options)->findOne(array(
			'_id' => Quark::ClassName($id) == 'MongoId' ? $id : new \MongoId((string)$id)
		), self::_fields($options), $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Update (IQuarkModel $model, $criteria, $options = []) {
		return $this->_collection($model, $options)->update($criteria, $model, $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Delete (IQuarkModel $model, $criteria, $options = []) {
		return $this->_collection($model, $options)->remove($criteria, $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $limit
	 * @param $skip
	 * @param $options
	 *
	 * @return int
	 */
	public function Count (IQuarkModel $model, $criteria, $limit, $skip, $options = []) {
		return $this->_collection($model, $options)->count($criteria, $options);
	}
}