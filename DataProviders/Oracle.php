<?php
namespace Quark\DataProviders;

use Quark\IQuarkDataProvider;
use Quark\IQuarkSQLDataProvider;
use Quark\IQuarkModel;

use Quark\QuarkField;
use Quark\QuarkKeyValuePair;
use Quark\QuarkURI;

/**
 * Class Oracle
 *
 * @package Quark\DataProviders
 */
class Oracle implements IQuarkDataProvider, IQuarkSQLDataProvider {
	/**
	 * @param QuarkURI $uri
	 *
	 * @return mixed
	 */
	public function Connect (QuarkURI $uri) {
		// TODO: Implement Connect() method.
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
	 *
	 * @return QuarkKeyValuePair
	 */
	public function PrimaryKey (IQuarkModel $model) {
		// TODO: Implement PrimaryKey() method.
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param             $options
	 *
	 * @return array
	 */
	public function Find (IQuarkModel $model, $criteria, $options) {
		// TODO: Implement Find() method.
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param             $options
	 *
	 * @return mixed
	 */
	public function FindOne (IQuarkModel $model, $criteria, $options) {
		// TODO: Implement FindOne() method.
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $id
	 * @param             $options
	 *
	 * @return mixed
	 */
	public function FindOneById (IQuarkModel $model, $id, $options) {
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
	 * @param             $options
	 *
	 * @return int
	 */
	public function Count (IQuarkModel $model, $criteria, $limit, $skip, $options) {
		// TODO: Implement Count() method.
	}

	/**
	 * @param string $query
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function Query ($query, $options) {
		// TODO: Implement Query() method.
	}

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	public function Escape ($value) {
		// TODO: Implement Escape() method.
	}

	/**
	 * @return string
	 */
	public function EscapeChar () {
		// TODO: Implement EscapeChar() method.
	}

	/**
	 * @param string $table
	 *
	 * @return QuarkField[]
	 */
	public function Schema ($table) {
		// TODO: Implement Schema() method.
	}

	/**
	 * @param IQuarkModel $model
	 * @param array $options
	 *
	 * @return string
	 */
	public function GenerateSchema (IQuarkModel $model, $options) {
		// TODO: Implement GenerateSchema() method.
	}

	/**
	 * @param $type
	 *
	 * @return string
	 */
	public function FieldTypeFromProvider ($type) {
		// TODO: Implement FieldTypeFromProvider() method.
	}

	/**
	 * @param $field
	 *
	 * @return string
	 */
	public function FieldTypeFromModel ($field) {
		// TODO: Implement FieldTypeFromModel() method.
	}
}