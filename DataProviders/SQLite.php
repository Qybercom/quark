<?php
namespace Quark\DataProviders;

use Quark\IQuarkDataProvider;
use Quark\IQuarkSQLDataProvider;
use Quark\IQuarkModel;

use Quark\Quark;
use Quark\QuarkConnectionException;
use Quark\QuarkField;
use Quark\QuarkKeyValuePair;
use Quark\QuarkModel;
use Quark\QuarkSQL;
use Quark\QuarkURI;

/**
 * Class SQLite
 *
 * @package Quark\DataProviders
 */
class SQLite implements IQuarkDataProvider, IQuarkSQLDataProvider {
	const PARAM_KEY = 'key';

	/**
	 * @var \SQLite3 $_connection
	 */
	private $_connection;

	/**
	 * @var QuarkSQL $_sql
	 */
	private $_sql;

	/**
	 * @var QuarkURI $_uri
	 */
	private $_uri;

	/**
	 * @param QuarkURI $uri
	 *
	 * @return mixed
	 *
	 * @throws QuarkConnectionException
	 */
	public function Connect (QuarkURI $uri) {
		$this->_uri = $uri;

		if (!class_exists('\\SQLite3'))
			throw new QuarkConnectionException($uri, Quark::LOG_FATAL, '[SQLite] Connection error: this PHP installation does not have configured SQLite extension');

		try {
			$this->_connection = new \SQLite3($uri->path, SQLITE3_OPEN_READWRITE|SQLITE3_OPEN_CREATE, $uri->Options(self::PARAM_KEY));

			$this->_sql = new QuarkSQL($this);
		}
		catch (\Exception $e) {
			throw new QuarkConnectionException($uri, Quark::LOG_FATAL, '[SQLite] ' . $e->getMessage());
		}
	}

	/**
	 * @param IQuarkModel $model
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function Create (IQuarkModel $model, $options = []) {
		$out = $this->_sql->Insert($model, $options);

		$pk = $this->PrimaryKey($model)->Key();
		$model->$pk = $this->_connection->lastInsertRowID();

		return $out;
	}

	/**
	 * @param IQuarkModel $model
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function Save (IQuarkModel $model, $options = []) {
		$pk = $this->PrimaryKey($model)->Key();

		if (!isset($model->$pk)) return false;

		return $this->Update($model, array(
			$pk => $model->$pk
		), $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function Remove (IQuarkModel $model, $options = []) {
		$pk = $this->PrimaryKey($model)->Key();

		if (!isset($model->$pk)) return false;

		return $this->Delete($model, array(
			$pk => $model->$pk
		), $options);
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return QuarkKeyValuePair
	 */
	public function PrimaryKey (IQuarkModel $model) {
		return $this->_sql->Pk($model, 'id', 0);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return array
	 */
	public function Find (IQuarkModel $model, $criteria, $options) {
		$output = array();

		/**
		 * @var \SQLite3Result $result
		 */
		$result= $this->_sql->Select($model, $criteria, $options);

		if ($result && $result->numColumns() != 0) {
			while ($record = $result->fetchArray(SQLITE3_ASSOC))
				$output[] = $record;
		}

		return $output;
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return mixed
	 */
	public function FindOne (IQuarkModel $model, $criteria, $options) {
		$records = $this->Find($model, $criteria, array_merge($options, array(QuarkModel::OPTION_LIMIT => 1)));

		return sizeof($records) == 0 ? null : $records[0];
	}

	/**
	 * @param IQuarkModel $model
	 * @param $id
	 * @param $options
	 *
	 * @return mixed
	 */
	public function FindOneById (IQuarkModel $model, $id, $options) {
		return $this->FindOne($model, array(
			$this->PrimaryKey($model)->Key() => $id
		), $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Update (IQuarkModel $model, $criteria, $options) {
		return $this->_sql->Update($model, $criteria, $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Delete (IQuarkModel $model, $criteria, $options) {
		return $this->_sql->Delete($model, $criteria, $options);
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
	public function Count (IQuarkModel $model, $criteria, $limit, $skip, $options) {
		$result = $this->_sql->Count($model, $criteria, array_merge($options, array(
			QuarkModel::OPTION_FIELDS => array(QuarkSQL::FIELD_COUNT_ALL),
			QuarkModel::OPTION_SKIP => $skip,
			QuarkModel::OPTION_LIMIT => $limit == 0 ? 1 : $limit
		)));

		return !$result ? 0 : (int)$result->fetchArray()[0];
	}

	/**
	 * @param string $query
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function Query ($query, $options) {
		$out = @$this->_connection->query($query);

		if (!$out)
			Quark::Log('[SQLite] Query error "' . $query . '". Error: ' . $this->_connection->lastErrorMsg());

		return $out;
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	public function EscapeCollection ($name) {
		return '"' . $name . '"';
	}

	/**
	 * @param string $field
	 *
	 * @return string
	 */
	public function EscapeField ($field) {
		return '"' . $field . '"';
	}

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	public function EscapeValue ($value) {
		return $value === null ? null : \SQLite3::escapeString($value);
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
	 * @param array $options = []
	 *
	 * @return mixed
	 */
	public function GenerateSchema (IQuarkModel $model, $options = []) {
		// TODO: Implement GenerateSchema() method.
	}
}