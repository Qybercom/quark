<?php
namespace Quark\Experimental;

use Quark\Quark;
use Quark\QuarkArchException;
use Quark\QuarkField;

use Quark\IQuarkModelWithBeforeCreate;
use Quark\IQuarkModelWithBeforeSave;
use Quark\IQuarkModelWithBeforeRemove;
use Quark\IQuarkModelWithBeforeValidate;
use Quark\IQuarkModelWithAfterFind;

use Quark\IQuarkDataProvider;

/**
 * Class MyModel
 *
 * @property \MongoId $_id
 * @property string $foo
 * @property string $bar
 *
 * @package QuarkExperimental
 */
class MyModel implements IQuarkModel, IQuarkModelWithDataProvider {
	/**
	 * @return mixed
	 */
	public function DataProvider () {
		// TODO: Implement DataProvider() method.
	}

	/**
	 * @return mixed
	 */
	public function Rules () {
		// TODO: Implement Rules() method.
	}

	/**
	 * @return array
	 */
	public function Fields () {
		return array(
			'_id' => new \MongoId(),
			'foo' => 'foo',
			'bar' => 'bar',
			'nested' => new NestedModel(),
			'length' => 100500,
			'pool' => new QuarkCollection(new NestedModel())
		);
	}
}

/**
 * Class NestedModel
 *
 * @package QuarkExperimental
 */
class NestedModel implements IQuarkModel, IQuarkLinkedModel {
	/**
	 * @return mixed
	 */
	public function Fields () {
		// TODO: Implement Rules() method.
	}

	/**
	 * @return mixed
	 */
	public function Rules () {
		// TODO: Implement Rules() method.
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Link ($raw) {
		return $raw;
	}

	/**
	 * @return mixed
	 */
	public function Unlink () {
		return array(
			'state' => 'unlinked'
		);
	}
}

/**
 * Class QuarkCollection
 *
 * @package Quark\Experimental
 */
class QuarkCollection {
	private $_list = array();
	private $_type = null;

	/**
	 * @param object $type
	 */
	public function __construct ($type) {
		$this->_type = $type;
	}

	/**
	 * @return mixed
	 */
	public function Type () {
		return $this->_type;
	}

	/**
	 * @param $item
	 *
	 * @return $this
	 */
	public function Add ($item) {
		if ($item instanceof $this->_type || ($item instanceof QuarkModel && $item->Model() instanceof $this->_type))
			$this->_list[] = $item;

		return $this;
	}

	/**
	 * @param array $source
	 * @param callable $iterator
	 *
	 * @return QuarkCollection
	 */
	public function PopulateWith ($source, callable $iterator = null) {
		if (!is_array($source)) return $this;

		if ($iterator == null)
			$iterator = function ($item) { return $item; };

		foreach ($source as $item)
			$this->Add($iterator($item));

		return $this;
	}

	/**
	 * @param callable $iterator
	 *
	 * @return array
	 */
	public function Collection (callable $iterator = null) {
		if ($iterator == null) return $this->_list;

		$output = array();

		foreach ($this->_list as $item)
			$output[] = $iterator($item);

		return $output;
	}
}

/**
 * Class QuarkModel
 *
 * @package QuarkExperimental
 */
class QuarkModel {
	const OPTION_EXTRACT = 'extract';
	const OPTION_VALIDATE = 'validate';

	/**
	 * @var IQuarkModel|null
	 */
	private $_model = null;

	public function __construct (IQuarkModel $model, $source = null) {
		/**
		 * Attention!
		 * Call of 'new' need to opposite non-controlled passing by reference
		 */
		$this->_model = new $model();

		$this->PopulateWith($source);
	}

	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	public function &__get ($key) {
		return $this->_model->$key;
	}

	/**
	 * @param $key
	 * @param $value
	 */
	public function __set ($key, $value) {
		$this->_model->$key = $value;
	}

	/**
	 * @param $method
	 * @param $args
	 *
	 * @return mixed
	 */
	public function __call ($method, $args) {
		return call_user_func_array(array($this->_model, $method), $args);
	}

	/**
	 * @return IQuarkModel
	 */
	public function Model () {
		return $this->_model;
	}

	/**
	 * @param $source
	 *
	 * @return QuarkModel
	 */
	public function PopulateWith ($source) {
		$this->_model = self::_import($this->_model, $source);

		return $this;
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return IQuarkDataProvider
	 * @throws QuarkArchException
	 */
	private static function _provider (IQuarkModel $model) {
		if (!($model instanceof IQuarkModelWithDataProvider))
			throw new QuarkArchException('Attempt to get data provider from model ' . get_class($model) . ' which is not defined as IQuarkStoredModel');

		$provider = $model->DataProvider();

		if (!($provider instanceof IQuarkDataProvider))
			throw new QuarkArchException('Model ' . get_class($model) . ' specified ' . (is_object($provider) ? get_class($provider) : gettype($provider)) . ', which is not a valid IQuarkDataProvider');

		return $provider;
	}

	/**
	 * @param IQuarkModel $model
	 * @param array       $fields
	 *
	 * @return IQuarkModel
	 */
	private static function _normalize (IQuarkModel $model, $fields = []) {
		if (func_num_args() == 1 || (!is_array($fields) && !is_object($fields)))
			$fields = $model->Fields();

		$output = $model;

		foreach ($fields as $key => $value)
			if (!isset($model->$key))
				$output->$key = $value;

		return $output;
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $source
	 * @param             $options
	 *
	 * @return IQuarkModel
	 */
	private static function _import (IQuarkModel $model, $source, $options = []) {
		if (!is_array($source) && !is_object($source)) return $model;

		$fields = $model->Fields();

		foreach ($source as $key => $value) {
			if (!Quark::PropertyExists($fields, $key) && $model instanceof IQuarkStrongModel) continue;

			$property = Quark::Property($fields, $key, $value);

			if ($property instanceof QuarkCollection) {
				$class = get_class($property->Type());

				$model->$key = $property->PopulateWith($value, function ($item) use ($class) {
					$output = new $class();

					return $output instanceof IQuarkLinkedModel ? $output->Link($item) : $item;
				});
			}
			else $model->$key = $property instanceof IQuarkLinkedModel
				? $property->Link($value)
				: ($property instanceof IQuarkModel ? new QuarkModel($property, $value) : $value);
		}

		return self::_normalize($model);
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $options
	 *
	 * @return IQuarkModel
	 */
	private static function _export (IQuarkModel $model, $options = []) {
		$output = $model;
		$fields = $model->Fields();

		if (!isset($options[self::OPTION_VALIDATE]))
			$options[self::OPTION_VALIDATE] = true;

		if ($options[self::OPTION_VALIDATE] && !self::_validate($model)) return false;

		foreach ($model as $key => $value) {
			if (!Quark::PropertyExists($fields, $key) && $model instanceof IQuarkStrongModel) continue;

			if ($value instanceof QuarkCollection) {
				$output->$key = $value->Collection(function ($item) {
					if ($item instanceof QuarkModel) $item = $item->Model();

					return $item instanceof IQuarkLinkedModel ? (object)$item->Unlink() : $item;
				});
			}
			else {
				if ($value instanceof QuarkModel) $value = $value->Model();

				$output->$key = $value instanceof IQuarkLinkedModel
					? (object)$value->Unlink()
					: $value;
			}
		}

		return self::_normalize($output);
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return bool
	 */
	private static function _validate (IQuarkModel $model) {
		if ($model instanceof IQuarkModelWithBeforeValidate && $model->BeforeValidate() === false) return false;

		return QuarkField::Rules($model->Rules());
	}

	/**
	 * @param array $fields
	 *
	 * @return \StdClass
	 */
	public function Extract ($fields = []) {
		$output = new \StdClass();

		foreach ($this->_model as $key => $value) {
			$property = Quark::Property($fields, $key, array());

			$output->$key = $value instanceof QuarkModel
				? $value->Extract($property)
				: ($value instanceof QuarkCollection
					? $value->Collection(function ($item) use ($property) {
						return $item instanceof QuarkModel ? $item->Extract($property) : $item;
					})
					: $value);
		}

		if (sizeof($fields) == 0) return $output;

		$buffer = new \StdClass();
		$property = null;

		//var_dump($fields);

		foreach ($fields as $field => $rule) {
			if (property_exists($output, $field))
				$buffer->$field = Quark::Property($output, $field, null);

			if (!is_bool($rule) && property_exists($output, (string)$rule))
				$buffer->$rule = Quark::Property($output, $rule, null);
		}

		return $buffer;
	}

	/**
	 * @return bool
	 */
	public function Validate () {
		return self::_validate($this->_model);
	}

	/**
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Create ($options = []) {
		$model = self::_export($this->_model, $options);

		if (!$model) return false;

		$ok = $model instanceof IQuarkModelWithBeforeCreate
			? $model->BeforeCreate($options)
			: true;

		return ($ok || $ok === null) ? self::_provider($model)->Create($model, $options) : false;
	}

	/**
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function Save ($options = []) {
		$model = self::_export($this->_model, $options);

		if (!$model) return false;

		print_r($model);

		$ok = $model instanceof IQuarkModelWithBeforeSave
			? $model->BeforeSave($options)
			: true;

		return ($ok || $ok === null) ? self::_provider($model)->Save($model, $options) : false;
	}

	/**
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Remove ($options = []) {
		$model = self::_export($this->_model, $options);

		if (!$model) return false;

		$ok = $model instanceof IQuarkModelWithBeforeRemove
			? $model->BeforeRemove($options)
			: true;

		return ($ok || $ok === null) ? self::_provider($model)->Remove($model, $options) : false;
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return array
	 */
	public static function Find (IQuarkModel $model, $criteria = [], $options = []) {
		$records = array();
		$raw = self::_provider($model)->Find($model, $criteria, $options);

		if ($raw == null)
			return array();

		foreach ($raw as $item)
			$records[] = self::_import($model, $item, $options);

		return $records;
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return QuarkModel|null
	 */
	public static function FindOne (IQuarkModel $model, $criteria = [], $options = []) {
		return self::_import($model, self::_provider($model)->FindOne($model, $criteria, $options), $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $id
	 * @param $options
	 *
	 * @return QuarkModel|null
	 */
	public static function FindOneById (IQuarkModel $model, $id, $options = []) {
		return self::_import($model, self::_provider($model)->FindOneById($model, $id, $options), $options);
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
	public static function Count (IQuarkModel $model, $criteria = [], $limit = 0, $skip = 0, $options = []) {
		return self::_provider($model)->Count($model, $criteria, $limit, $skip, $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return mixed
	 */
	public static function Update (IQuarkModel $model, $criteria = [], $options = []) {
		$model = self::_export($model, $options);

		if (!$model) return false;

		$ok = $model instanceof IQuarkModelWithBeforeSave
			? $model->BeforeSave($options)
			: true;

		return ($ok || $ok === null) ? self::_provider($model)->Update($model, $criteria, $options) : false;
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return mixed
	 */
	public static function Delete (IQuarkModel $model, $criteria = [], $options = []) {
		$model = self::_export($model, $options);

		if (!$model) return false;

		$ok = $model instanceof IQuarkModelWithBeforeRemove
			? $model->BeforeRemove($options)
			: true;

		return ($ok || $ok === null) ? self::_provider($model)->Delete($model, $criteria, $options) : false;
	}
}

/**
 * Interface IQuarkModel
 *
 * @package QuarkExperimental
 */
interface IQuarkModel {
	/**
	 * @return mixed
	 */
	function Fields();

	/**
	 * @return mixed
	 */
	function Rules();
}

/**
 * Interface IQuarkModelWithDataProvider
 *
 * @package Quark\Experimental
 */
interface IQuarkModelWithDataProvider {
	/**
	 * @return mixed
	 */
	function DataProvider();
}

/**
 * Interface IQuarkLinkedModel
 *
 * @package Quark\Experimental
 */
interface IQuarkLinkedModel {
	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	function Link($raw);

	/**
	 * @return mixed
	 */
	function Unlink();
}

/**
 * Interface IQuarkStrongModel
 *
 * @package Quark\Experimental
 */
interface IQuarkStrongModel { }