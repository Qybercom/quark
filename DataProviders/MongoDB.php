<?php
namespace Quark\DataProviders;

use Quark\IQuarkDataProvider;
use Quark\IQuarkModel;

use Quark\Quark;
use Quark\QuarkArchException;
use Quark\QuarkURI;
use Quark\QuarkConnectionException;

/**
 * Class MongoDB
 *
 * @package Quark\DataProviders
 */
class MongoDB implements IQuarkDataProvider {
	/**
	 * @var \MongoDb $_connection
	 */
	private $_connection;

	/**
	 * @var QuarkURI
	 */
	private $_uri;

	/**
	 * @param object $source
	 *
	 * @return string
	 */
	public static function _id ($source) {
		if (\MongoId::isValid($source)) return $source;
		if (!is_object($source)) return '';

		return isset($source->_id->{'$id'})
			? $source->_id->{'$id'}
			: (isset($source->_id) && \MongoId::isValid($source->_id) ? $source->_id : '');
	}

	/**
	 * @param array $source
	 * @param array $exclude
	 *
	 * @return array
	 */
	public static function _ids ($source = [], $exclude = []) {
		if (!is_array($source)) return array();

		if (is_string($exclude))
			$exclude = array($exclude);

		if (!is_array($exclude))
			$exclude = array();

		$ids = array();

		foreach ($source as $id)
			if (\MongoId::isValid($id) && !in_array($id, $exclude))
				$ids[] = new \MongoId($id);

		return $ids;
	}

	/**
	 * @return bool
	 */
	public static function CompareIds () {
		$ids = func_get_args();

		if (sizeof($ids) == 0) return true;

		$prev = $ids[0];
		$out = true;

		foreach ($ids as $id) {
			$out = $out && self::_id($id) == self::_id($prev);
			$prev = $id;
		}

		return $out;
	}

	/**
	 * @param QuarkURI $uri
	 *
	 * @return mixed
	 * @throws QuarkConnectionException
	 */
	public function Connect (QuarkURI $uri) {
		try {
			$options = array();

			if (is_array($uri->options))
				$options = $uri->options;

			$this->_connection = new \MongoClient($uri->URI(), $options);
			$uri->path = str_replace('/', '', $uri->path);

			if (strlen(trim($uri->path)) != 0) {
				$db = $uri->path;
				$this->_connection = $this->_connection->$db;
				$this->_uri = $uri;
			}
		}
		catch (\Exception $e) {
			throw new QuarkConnectionException($uri, Quark::LOG_FATAL);
		}
	}

	/**
	 * @return QuarkURI
	 */
	public function SourceURI () {
		return $this->_uri;
	}

	/**
	 * @param IQuarkModel $model
	 * @param $options
	 *
	 * @return mixed
	 * @throws QuarkArchException
	 */
	private function _collection ($model ,$options) {
		$collection = isset($options['collection'])
			? $options['collection']
			: Quark::ClassOf($model);

		if ($this->_connection == null)
			throw new QuarkArchException('MongoDB connection not pooled');

		return $this->_connection->$collection;
	}

	/**
	 * @param IQuarkModel $model
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Create (IQuarkModel $model, $options = []) {
		unset($model->_id);

		return $this->_collection($model, $options)->insert($model, $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Save (IQuarkModel $model, $options = []) {
		$model->_id = new \MongoId(self::_id($model));

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
			'_id' => new \MongoId(self::_id($model))
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
	 * @param $raw
	 *
	 * @return mixed
	 */
	private static function _record ($raw) {
		return is_array($raw) || is_object($raw) ? $raw : null;
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return array
	 */
	public function Find (IQuarkModel $model, $criteria, $options = []) {
		/**
		 * @var \MongoCursor $raw
		 */
		$raw = $this->_collection($model, $options)->find($criteria, self::_fields($options));

		if (isset($options['sort']))
			$raw->sort($options['sort']);

		if (isset($options['limit']))
			$raw->limit($options['limit']);

		if (isset($options['skip']))
			$raw->skip($options['skip']);

		$buffer = array();
		$item = null;

		foreach ($raw as $document) {
			$item = $document;

			if (isset($options['getId']) && $options['getId'] == true)
				$item->_id = $document->_id->{'$id'};

			$buffer[] = self::_record($item);
		}

		return $buffer;
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return mixed
	 */
	public function FindOne (IQuarkModel $model, $criteria, $options = []) {
		return self::_record($this->_collection($model, $options)->findOne($criteria, self::_fields($options)/*, $options*/));
	}

	/**
	 * @param IQuarkModel $model
	 * @param $id
	 * @param $options
	 *
	 * @return mixed
	 */
	public function FindOneById (IQuarkModel $model, $id, $options = []) {
		if (!\MongoId::isValid($id)) return null;

		return self::_record($this->_collection($model, $options)->findOne(array(
			'_id' => $id instanceof \MongoId ? $id : new \MongoId($id)
		), self::_fields($options)/*, $options*/));
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