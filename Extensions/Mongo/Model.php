<?php
namespace Quark\Extensions\Mongo;

use Quark\Quark;
use Quark\QuarkField;

use Quark\QuarkArchException;

use Quark\IQuarkModel;
use Quark\IQuarkAuthorizableModel;
use Quark\IQuarkAuthorizableDataProvider;

/**
 * Class Model
 * @package Quark\Extensions\Mongo
 */
class Model implements IQuarkModel, IQuarkAuthorizableDataProvider {
	/**
	 * @var IMongoModel|IMongoModelWithBeforeSave|IMongoModelWithBeforeRemove|IQuarkAuthorizableModel
	 */
	private $_model;

	/**
	 * @param mixed $target
	 * @throws QuarkArchException
	 * @return \MongoCollection
	 */
	private static function _source ($target) {
		$collection = $target;

		if (!is_object($target)) {
			$target = 'Models\\' . $target;

			if (!class_exists($target))
				throw new QuarkArchException('Unrecognized data model ' . print_r($target, true));
		}
		else {
			$target = '\\' . get_class($target);
			$collection = str_replace('\\Models\\', '', $target);
		}

		return Source::Get($target::Storage())->$collection;
	}

	/**
	 * @param $key
	 * @param $value
	 * @return mixed
	 */
	private static function _id ($key, $value) {
		return $key == '_id' ? $value->{'$id'} : $value;
	}

	/**
	 * @param $model
	 * @param $raw
	 * @return IMongoModel|IMongoModelWithAfterFind
	 */
	private static function _record ($model, $raw) {
		$model = '\\Models\\' . $model;

		/**
		 * @var IMongoModel|IMongoModelWithAfterFind $record
		 */
		$record = new $model();
		$schema = $record->Fields();

		$buffer = Quark::is($model, 'Quark\Extensions\Mongo\IMongoModelWithAfterFind')
			? $record->AfterFind($raw)
			: $raw;

		if ($buffer == null) return null;

		foreach ($schema as $key => $value)
			$record->$key = self::_id($key, isset($buffer[$key]) ? $buffer[$key] : $value);

		return $record;
	}

	/**
	 * @param IMongoModel $model
	 * @param mixed $source
	 */
	public function __construct (IMongoModel $model, $source = null) {
		$this->_model = $model;

		if (func_num_args() == 2)
			$this->PopulateWith($source);
	}

	/**
	 * @param IMongoModel $model
	 * @return mixed
	 */
	public function Model ($model = null) {
		if (func_num_args() == 1)
			$this->_model = $model;

		return $this->_model;
	}

	/**
	 * @param array $input
	 * @return Model
	 */
	public function PopulateWith ($input = []) {
		$data = Quark::DataArray($input, $this->_model->Fields());

		foreach ($data as $key => $value)
			$this->_model->$key = $value;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function Validate () {
		return QuarkField::Rules($this->_model->Rules());
	}

	/**
	 * @param $options
	 * @return mixed
	 */
	public function Save ($options = []) {
		if (
			Quark::is(
				$this->_model,
				'Quark\Extensions\Mongo\IMongoModelWithBeforeSave'
			)
			&& !$this->_model->BeforeSave()
		) return false;

		if (!is_array($options)) $options = array();

		return self::_source($this->_model)->save($this->_model, $options);
	}

	/**
	 * @param $options
	 * @return mixed
	 */
	public function Remove ($options = []) {
		if (
			Quark::is(
				$this->_model,
				'Quark\Extensions\Mongo\IMongoModelWithBeforeRemove'
			)
			&& !$this->_model->BeforeRemove()
		) return false;

		if (!is_array($options)) $options = array();

		return self::_source($this->_model)->remove(array(
			'_id' => new \MongoId($this->_model)
		), $options);
	}

	/**
	 * @param string $model
	 * @param $criteria
	 * @param $options
	 * @return mixed
	 */
	public static function Find ($model, $criteria = [], $options = []) {
		if (!is_array($criteria)) $criteria = array();
		if (!is_array($options)) $options = array();

		$raw = self::_source($model)->find($criteria);

		if (isset($options['sort']))
			$raw->sort($options['sort']);

		if (isset($options['limit']))
			$raw->limit($options['limit']);

		if (isset($options['skip']))
			$raw->skip($options['skip']);

		$records = array();

		foreach ($raw as $i => $item)
			$records[] = self::_record($model, $item);

		return $records;
	}

	/**
	 * @param $model
	 * @param $criteria
	 * @return IMongoModel|IMongoModelWithAfterFind
	 */
	public static function FindOne ($model, $criteria = []) {
		if (!is_array($criteria)) $criteria = array();

		return self::_record($model, self::_source($model)->findOne($criteria));
	}

	/**
	 * @param $model
	 * @param $id
	 * @return IMongoModel|IMongoModelWithAfterFind
	 */
	public static function GetById ($model, $id) {
		return self::_record($model, self::_source($model)->findOne(array(
			'_id' => new \MongoId($id)
		)));
	}

	/**
	 * @param $model
	 * @param array $criteria
	 * @param array $options
	 * @return bool
	 */
	public static function Update ($model, $criteria = [], $options = []) {
		if (!is_array($criteria)) $criteria = array();
		if (!is_array($options)) $options = array();

		return self::_source($model)->update($criteria, $options);
	}

	/**
	 * @param string $model
	 * @param $criteria
	 * @param $options
	 * @return mixed
	 */
	public static function Delete ($model, $criteria = [], $options = []) {
		if (!is_array($criteria)) $criteria = array();
		if (!is_array($options)) $options = array();

		return self::_source($model)->remove($criteria, $options);
	}

	/**
	 * @param $model
	 * @param array $criteria
	 * @param int $limit
	 * @param int $skip
	 * @return int
	 */
	public static function Count ($model, $criteria = [], $limit = 0, $skip = 0) {
		if (!is_array($criteria)) $criteria = array();

		return self::_source($model)->count($criteria, $limit, $skip);
	}

	/**
	 * @return IQuarkAuthorizableModel
	 */
	public function Authenticate () {
		return self::FindOne(str_replace('Models\\', '', get_class($this->_model)), $this->_model->LoginCriteria());
	}
}