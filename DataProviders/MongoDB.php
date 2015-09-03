<?php
namespace Quark\DataProviders;

use Quark\IQuarkDataProvider;
use Quark\IQuarkModel;

use Quark\Quark;
use Quark\QuarkObject;
use Quark\QuarkArchException;
use Quark\QuarkModel;
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
	 * @param $id
	 *
	 * @return bool
	 */
	public static function IsValidId ($id) {
		try {
			return (bool)new \MongoId($id);
		}
		catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * @param object $source
	 *
	 * @return string
	 */
	public static function _id ($source) {
		if (self::IsValidId($source)) return (string)$source;

		if (is_array($source))
			$source = (object)$source;

		if (isset($source->_id)) {
			if (is_array($source->_id))
				$source->_id = (object)$source->_id;

			if (self::IsValidId($source->_id))
				return (string)$source->_id;
		}

		if (isset($source->{'$id'}) && self::IsValidId($source->{'$id'}))
			return (string)$source->{'$id'};

		if (isset($source->_id->{'$id'}) && self::IsValidId($source->_id->{'$id'}))
			return (string)$source->_id->{'$id'};

		return '';
	}

	/**
	 * @param IQuarkModel $model
	 * @param bool $_id
	 *
	 * @return mixed
	 */
	private function _data (IQuarkModel $model, $_id = true) {
		$out = json_decode(json_encode($model));

		if ($_id) $out->_id = new \MongoId(self::_id($model));
		else unset($out->_id);

		return $out;
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
			if (self::IsValidId($id) && !in_array($id, $exclude, true))
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
	 *
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
			}
		}
		catch (\Exception $e) {
			throw new QuarkConnectionException($uri, Quark::LOG_FATAL);
		}
	}

	/**
	 * @param IQuarkModel $model
	 * @param $options
	 *
	 * @return mixed
	 * @throws QuarkArchException
	 */
	private function _collection ($model ,$options) {
		$collection = isset($options[QuarkModel::OPTION_COLLECTION])
			? $options[QuarkModel::OPTION_COLLECTION]
			: QuarkObject::ClassOf($model);

		if ($this->_connection == null)
			throw new QuarkArchException('MongoDB connection not pooled');

		return $this->_connection->$collection;
	}

	/**
	 * @param IQuarkModel|\StdClass $model
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Create (IQuarkModel $model, $options = []) {
		$data = $this->_data($model, false);
		$out = $this->_collection($model, $options)->insert($data, $options);

		$model->_id = $data->_id;

		return $out;
	}

	/**
	 * @param IQuarkModel $model
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Save (IQuarkModel $model, $options = []) {
		return $this->_collection($model, $options)->save($this->_data($model), $options);
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
		return isset($options[QuarkModel::OPTION_FIELDS]) && is_array($options[QuarkModel::OPTION_FIELDS])
			? $options[QuarkModel::OPTION_FIELDS]
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
	 *
	 * @return string
	 */
	public function PrimaryKey (IQuarkModel $model) {
		return '_id';
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

		if (isset($options[QuarkModel::OPTION_SORT]))
			$raw->sort($options[QuarkModel::OPTION_SORT]);

		if (isset($options[QuarkModel::OPTION_LIMIT]))
			$raw->limit($options[QuarkModel::OPTION_LIMIT]);

		if (isset($options[QuarkModel::OPTION_SKIP]))
			$raw->skip($options[QuarkModel::OPTION_SKIP]);

		$buffer = array();
		$item = null;

		foreach ($raw as $document) {
			/**
			 * @var \StdClass $document->_id
			 */
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
		return self::_record($this->_collection($model, $options)->findOne($criteria, self::_fields($options)));
	}

	/**
	 * @param IQuarkModel $model
	 * @param $id
	 * @param $options
	 *
	 * @return mixed
	 */
	public function FindOneById (IQuarkModel $model, $id, $options = []) {
		$id = self::_id($id);

		if (!self::IsValidId($id)) return null;

		return self::_record($this->_collection($model, $options)->findOne(array(
			'_id' => new \MongoId($id)
		), self::_fields($options)));
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Update (IQuarkModel $model, $criteria, $options = []) {
		return $this->_collection($model, $options)->update($criteria, $this->_data($model, false), $options);
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
		return $this->_collection($model, $options)->count($criteria, sizeof($options) != 0 ? $options : null);
	}
}