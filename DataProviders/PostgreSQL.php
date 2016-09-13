<?php
namespace Quark\DataProviders;

use Quark\IQuarkModel;
use Quark\IQuarkDataProvider;
use Quark\IQuarkSQLDataProvider;

use Quark\Quark;
use Quark\QuarkDate;
use Quark\QuarkException;
use Quark\QuarkField;
use Quark\QuarkKeyValuePair;
use Quark\QuarkModel;
use Quark\QuarkURI;
use Quark\QuarkSQL;
use Quark\QuarkConnectionException;

/**
 * Class PostgreSQL
 *
 * @package Quark\DataProviders
 */
class PostgreSQL implements IQuarkDataProvider, IQuarkSQLDataProvider {
	const OPTION_SCHEMA_ENCODING = 'ENCODING';
	const OPTION_SCHEMA_LC_COLLATE = 'LC_COLLATE';
	const OPTION_SCHEMA_LC_CTYPE = 'LC_CTYPE';
	const OPTION_SCHEMA_PRIMARY_KEY = 'PRIMARY KEY';
	const OPTION_SCHEMA_NOT_NULL = 'NOT NULL';
	const OPTION_SCHEMA_CHECK_EXISTS = '_sql_exists';

	const DEFAULT_ENCODING = 'UTF8';

	const TYPE_INT = 'INT';
	const TYPE_NUMERIC = 'NUMERIC';
	const TYPE_REAL = 'REAL';
	const TYPE_DOUBLE = 'DOUBLE';
	const TYPE_BOOLEAN = 'BOOLEAN';
	const TYPE_DATE = 'DATE';
	const TYPE_TIMESTAMP = 'TIMESTAMP';
	const TYPE_TEXT = 'TEXT';
	const TYPE_BIGSERIAL = 'BIGSERIAL';

	/**
	 * @var resource $_connection
	 */
	private $_connection;

	/**
	 * @var QuarkSQL $_sql
	 */
	private $_sql;

	/**
	 * @param QuarkURI $uri
	 *
	 * @return void
	 *
	 * @throws QuarkConnectionException
	 */
	public function Connect (QuarkURI $uri) {
		$this->_connection = @\pg_connect(
			'host=\'' . $uri->host . '\'' .
			'port=\'' . $uri->port . '\'' .
			'dbname=\'' . QuarkSQL::DBName($uri->path) . '\'' .
			'user=\'' . $uri->user . '\'' .
			'password=\'' . $uri->pass . '\'' .
			'options=\'' . $uri->options . '\''
		);

		if (!$this->_connection)
			throw new QuarkConnectionException($uri, Quark::LOG_FATAL, QuarkException::LastError());

		$this->_sql = new QuarkSQL($this);
	}

	/**
	 * @param IQuarkModel $model
	 * @param array       $options
	 *
	 * @return mixed
	 */
	public function Create (IQuarkModel $model, $options = []) {
		return $this->_sql->Insert($model, $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param array       $options
	 *
	 * @return mixed
	 */
	public function Save (IQuarkModel $model, $options = []) {
		$pk = $this->_sql->Pk($model);

		if (!isset($model->$pk)) return false;

		return $this->Update($model, array(
			$pk => $model->$pk
		), $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param array       $options
	 *
	 * @return mixed
	 */
	public function Remove (IQuarkModel $model, $options = []) {
		$pk = $this->_sql->Pk($model);

		if (!isset($model->$pk)) return false;

		return $this->Delete($model, array(
			$pk => $model->$pk
		), $options);
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return string
	 */
	public function PrimaryKey (IQuarkModel $model) {
		return new QuarkKeyValuePair('id', 0);
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param array       $options
	 *
	 * @return array
	 */
	public function Find (IQuarkModel $model, $criteria, $options = []) {
		$output = array();
		$records = $this->_sql->Select($model, $criteria, $options);

		if ($records) {
			$out = \pg_fetch_all($records);

			if ($out)
				foreach ($out as $record)
					$output[] = $record;
		}

		return $output;
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param array       $options
	 *
	 * @return mixed
	 */
	public function FindOne (IQuarkModel $model, $criteria, $options = []) {
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
	public function FindOneById (IQuarkModel $model, $id, $options = []) {
		return $this->FindOne($model, array(
			$this->_sql->Pk($model) => $id
		), $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param             $options
	 *
	 * @return mixed
	 */
	public function Update (IQuarkModel $model, $criteria, $options) {
		return $this->_sql->Update($model, $criteria, $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param             $options
	 *
	 * @return mixed
	 */
	public function Delete (IQuarkModel $model, $criteria, $options) {
		return $this->_sql->Delete($model, $criteria, $options);
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
	public function Count (IQuarkModel $model, $criteria, $limit, $skip, $options = []) {
		$result = $this->_sql->Count($model, $criteria, array_merge($options, array(
			QuarkModel::OPTION_FIELDS => array(QuarkSQL::FIELD_COUNT_ALL),
			QuarkModel::OPTION_SKIP => $skip,
			QuarkModel::OPTION_LIMIT => $limit == 0 ? 'ALL' : $limit
		)));

		if (!$result) return 0;

		$out = \pg_fetch_assoc($result);

		return isset($out['count']) ? (int)$out['count'] : 0;
	}

	/**
	 * @param string $query
	 * @param array  $options
	 *
	 * @return mixed
	 */
	public function Query ($query, $options) {
		$out = \pg_query($this->_connection, $query . ';');

		if (!$out)
			Quark::Log('[PostgreSQL] Query error "' . $query . '". Error: ' . \pg_last_error($this->_connection));

		return $out;
	}

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	public function Escape ($value) {
		return \pg_escape_string($this->_connection, $value);
	}

	/**
	 * @return string
	 */
	public function EscapeChar () {
		return '"';
	}

	/**
	 * @param string $table
	 *
	 * @return QuarkField[]
	 */
	public function Schema ($table) {
		$result = $this->Query('SELECT * FROM information_schema.columns WHERE table_name = \'' . $table . '\'', []);

		if (!$result) return array();

		$schema = pg_fetch_all($result);
		$output = array();

		foreach ($schema as $field) {
			if (!array_key_exists('column_name', $field)) continue;
			if (!array_key_exists('column_default', $field)) continue;
			if (!array_key_exists('data_type', $field)) continue;

			$value = $field['column_default'];
			$type = $this->_sql->FieldTypeFromProvider($field['column_default']);

			if ($type == QuarkField::TYPE_DATE) $value = new QuarkDate();
			elseif ($type == QuarkField::TYPE_TIMESTAMP) $value = QuarkDate::FromTimestamp();
			else settype($value, $type);

			$output[] = new QuarkField($field['column_name'], $type, $value);
		}

		return $output;
	}

	/**
	 * @param IQuarkModel $model
	 * @param array $options = []
	 *
	 * @return mixed
	 */
	public function GenerateSchema (IQuarkModel $model, $options = []) {
		if (!isset($options[QuarkSQL::OPTION_SCHEMA_GENERATE_PRINT]))
			$options[QuarkSQL::OPTION_SCHEMA_GENERATE_PRINT] = true;

		if (!isset($options[self::OPTION_SCHEMA_CHECK_EXISTS]))
			$options[self::OPTION_SCHEMA_CHECK_EXISTS] = true;

		if (!isset($options[self::OPTION_SCHEMA_ENCODING]))
			$options[self::OPTION_SCHEMA_ENCODING] = self::DEFAULT_ENCODING;

		$pk = QuarkSQL::Pk($model);
		$fields = '';
		$properties = $model->Fields();

		foreach ($properties as $key => $value) {
			$type = $this->_sql->FieldTypeFromModel($value);
			$fields .= '"' . $this->_sql->Field($key) . '"'
				. ' ' . ($key == $pk ? self::TYPE_BIGSERIAL . ' ' . self::OPTION_SCHEMA_PRIMARY_KEY : $type)
				. ' ' . self::OPTION_SCHEMA_NOT_NULL . ', ';
		}

		$fields = trim($fields, " ,");

		return $this->_sql->Query(
			$model,
			$options,
			'CREATE TABLE '
			. ($options[self::OPTION_SCHEMA_CHECK_EXISTS] ? 'IF NOT EXISTS ' : '')
			. QuarkSQL::Collection($model)
			. ' (' . $fields . ');',
			$options[QuarkSQL::OPTION_SCHEMA_GENERATE_PRINT]
		);
	}

	/**
	 * @param $type
	 *
	 * @return string
	 */
	public function FieldTypeFromProvider ($type) {
		$t = strtoupper($type);

		if (strstr($t, self::TYPE_INT)) return QuarkField::TYPE_INT;
		if ($t == self::TYPE_NUMERIC || $t == self::TYPE_REAL || $t == self::TYPE_DOUBLE) return QuarkField::TYPE_FLOAT;
		if ($t == self::TYPE_BOOLEAN) return QuarkField::TYPE_BOOL;
		if (strstr($t, self::TYPE_DATE)) return QuarkField::TYPE_DATE;
		if (strstr($t, self::TYPE_TIMESTAMP)) return QuarkField::TYPE_TIMESTAMP;

		return QuarkField::TYPE_STRING;
	}

	/**
	 * @param $field
	 *
	 * @return string
	 */
	public function FieldTypeFromModel ($field) {
		$type = QuarkField::TypeOf($field);

		if ($type == QuarkField::TYPE_INT) return self::TYPE_INT;
		if ($type == QuarkField::TYPE_FLOAT) return self::TYPE_DOUBLE;
		if ($type == QuarkField::TYPE_BOOL) return self::TYPE_BOOLEAN;
		if ($type == QuarkField::TYPE_DATE) return self::TYPE_DATE;
		if ($type == QuarkField::TYPE_TIMESTAMP) return self::TYPE_TIMESTAMP;

		return self::TYPE_TEXT;
	}
}