<?php
namespace Quark\DataProviders;

use Quark\IQuarkDataProvider;
use Quark\IQuarkSQLDataProvider;
use Quark\IQuarkModel;

use Quark\Quark;
use Quark\QuarkDate;
use Quark\QuarkException;
use Quark\QuarkField;
use Quark\QuarkKeyValuePair;
use Quark\QuarkModel;
use Quark\QuarkURI;
use Quark\QuarkSQL;
use Quark\QuarkArchException;
use Quark\QuarkConnectionException;

/**
 * Class MySQL
 *
 * @package Quark\DataProviders
 */
class MySQL implements IQuarkDataProvider, IQuarkSQLDataProvider {
	const OPTION_SCHEMA_TYPE = 'TYPE';
	const OPTION_SCHEMA_CHARSET = 'CHARSET';
	const OPTION_SCHEMA_COLLATE = 'COLLATE';
	const OPTION_SCHEMA_AUTOINCREMENT = 'AUTO_INCREMENT';
	const OPTION_SCHEMA_NOT_NULL = 'NOT NULL';
	const OPTION_SCHEMA_CHECK_EXISTS = '_sql_exists';

	const SCHEMA_TYPE_BDB = 'BDB';
	const SCHEMA_TYPE_HEAP = 'HEAP';
	const SCHEMA_TYPE_ISAM = 'ISAM';
	const SCHEMA_TYPE_INNODB = 'InnoDB';
	const SCHEMA_TYPE_MERGE = 'MERGE';
	const SCHEMA_TYPE_MRG_MYISAM = 'MRG_MYISAM';
	const SCHEMA_TYPE_MYISAM = 'MYISAM';

	const DEFAULT_CHARSET = 'utf8';
	const DEFAULT_COLLATE = 'utf8_bin';
	const DEFAULT_AUTOINCREMENT = 1;

	const TYPE_INT = 'INT';
	const TYPE_DECIMAL = 'DECIMAL';
	const TYPE_FLOAT = 'FLOAT';
	const TYPE_DOUBLE = 'DOUBLE';
	const TYPE_REAL = 'REAL';
	const TYPE_BOOLEAN = 'BOOLEAN';
	const TYPE_TINYINT1 = 'TINYINT(1)';
	const TYPE_DATE = 'DATE';
	const TYPE_DATETIME = 'DATETIME';
	const TYPE_TEXT = 'TEXT';

	const MYSQLI_RECONNECT = 'mysqli.reconnect';

	/**
	 * @var \mysqli $_connection
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
	 * @return void
	 *
	 * @throws QuarkArchException
	 * @throws QuarkConnectionException
	 */
	public function Connect (QuarkURI $uri) {
		$this->_uri = $uri;

		if (!function_exists('mysqli_init'))
			throw new QuarkConnectionException($uri, Quark::LOG_FATAL, '[MySQL] Connection error: this PHP installation does not have configured MySQL extension');

		$this->_connection = \mysqli_init();

		if (!$this->_connection)
			throw new QuarkArchException('MySQLi initialization fault');

		if (ini_get(self::MYSQLI_RECONNECT) == 1)
			ini_set(self::MYSQLI_RECONNECT, 0);

		$options = $uri->Options();

		foreach ($options as $key => $value) {
			if (!$this->_connection->options($key, $value))
				throw new QuarkArchException('MySQLi option set error');
		}

		if (!@$this->_connection->real_connect(
			$uri->host,
			$uri->user,
			$uri->pass,
			QuarkSQL::DBName($uri->path),
			(int)$uri->port
		))
			throw new QuarkConnectionException($uri, Quark::LOG_FATAL, QuarkException::LastError());

		$this->_connection->set_charset(self::DEFAULT_CHARSET);
		$this->_sql = new QuarkSQL($this);
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
		$model->$pk = $this->_connection->insert_id;

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
	 * @param array $options
	 *
	 * @return array
	 */
	public function Find (IQuarkModel $model, $criteria, $options = []) {
		$output = array();
		$records = $this->_sql->Select($model, $criteria, $options);

		if ($records)
			foreach ($records as $record)
				$output[] = $record;

		return $output;
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param array $options
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
	public function Count (IQuarkModel $model, $criteria, $limit, $skip, $options = []) {
		$result = $this->_sql->Count($model, $criteria, array_merge($options, array(
				QuarkModel::OPTION_FIELDS => array(QuarkSQL::FIELD_COUNT_ALL),
				QuarkModel::OPTION_SKIP => $skip,
				QuarkModel::OPTION_LIMIT => $limit == 0 ? 1 : $limit
			)));

		return !$result ? 0 : (int)$result->fetch_row()[0];
	}

	/**
	 * @param string $query
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function Query ($query, $options) {
		$mode = isset($options['mode'])
			? $options['mode']
			: MYSQLI_STORE_RESULT;

		if (!@$this->_connection->ping())
			$this->Connect($this->_uri);

		$out = @$this->_connection->query($query, $mode);

		if (!$out)
			Quark::Log('[MySQL] Query error "' . $query . '". Error: ' . $this->_connection->error);

		return $out;
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	public function EscapeCollection ($name) {
		return '`' . $name . '`';
	}

	/**
	 * @param string $field
	 *
	 * @return string
	 */
	public function EscapeField ($field) {
		return '`' . $field . '`';
	}

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	public function EscapeValue ($value) {
		return $this->_connection->real_escape_string($value);
	}

	/**
	 * @param string $table
	 *
	 * @return QuarkField[]
	 */
	public function Schema ($table) {
		$schema = $this->Query('SHOW COLUMNS FROM ' . $table, []);

		if (!$schema) return array();

		$output = array();

		foreach ($schema as $field) {
			if (!array_key_exists('Field', $field)) continue;
			if (!array_key_exists('Type', $field)) continue;
			if (!array_key_exists('Default', $field)) continue;

			$value = $field['Default'];
			$type = $this->_sql->FieldTypeFromProvider($field['Type']);

			if ($type == QuarkField::TYPE_DATE) $value = new QuarkDate();
			else settype($value, $type);

			$output[] = new QuarkField($field['Field'], $type, $value);
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

		if (!isset($options[self::OPTION_SCHEMA_TYPE]))
			$options[self::OPTION_SCHEMA_TYPE] = self::SCHEMA_TYPE_INNODB;

		if (!isset($options[self::OPTION_SCHEMA_CHARSET]))
			$options[self::OPTION_SCHEMA_CHARSET] = self::DEFAULT_CHARSET;

		if (!isset($options[self::OPTION_SCHEMA_COLLATE]))
			$options[self::OPTION_SCHEMA_COLLATE] = self::DEFAULT_COLLATE;

		if (!isset($options[self::OPTION_SCHEMA_AUTOINCREMENT]))
			$options[self::OPTION_SCHEMA_AUTOINCREMENT] = self::DEFAULT_AUTOINCREMENT;

		$pk = $this->PrimaryKey($model)->Key();
		$fields = '';
		$properties = $model->Fields();

		foreach ($properties as $key => $value) {
			$type = $this->_sql->FieldTypeFromModel($value);
			$fields .= $this->_sql->Field($key) . ' ' . $type;

			if ($key == $pk)
				$fields .=
					' ' . self::OPTION_SCHEMA_NOT_NULL .
					' ' . self::OPTION_SCHEMA_AUTOINCREMENT;

			if ($type == self::TYPE_DATETIME)
				$fields .=
					' ' . self::OPTION_SCHEMA_NOT_NULL;

			if ($type == self::TYPE_TEXT)
				$fields .=
					' ' . self::OPTION_SCHEMA_COLLATE .
					' ' . $options[self::OPTION_SCHEMA_COLLATE] .
					' ' . self::OPTION_SCHEMA_NOT_NULL;

			$fields .= ', ';
		}

		$fields .= 'PRIMARY KEY (`' . $pk . '`)';

		return $this->_sql->Query(
			$model,
			$options,
			'CREATE TABLE '
			. ($options[self::OPTION_SCHEMA_CHECK_EXISTS] ? 'IF NOT EXISTS ' : '')
			. QuarkSQL::Collection($model)
			. ' (' . $fields . ') '
			. 'ENGINE=' . $options[self::OPTION_SCHEMA_TYPE]
			. ' DEFAULT'
				. ' CHARSET=' . $options[self::OPTION_SCHEMA_CHARSET]
				. ' COLLATE=' . $options[self::OPTION_SCHEMA_COLLATE]
				. ' AUTO_INCREMENT=' . $options[self::OPTION_SCHEMA_AUTOINCREMENT] . ' ;',
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
		if ($t == self::TYPE_DECIMAL || $t == self::TYPE_FLOAT || $t == self::TYPE_DOUBLE || $t == self::TYPE_REAL) return QuarkField::TYPE_FLOAT;
		if ($t == self::TYPE_BOOLEAN || $t == self::TYPE_TINYINT1) return QuarkField::TYPE_BOOL;
		if (strstr($t, self::TYPE_DATE)) return QuarkField::TYPE_DATE;

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
		if ($type == QuarkField::TYPE_BOOL) return self::TYPE_TINYINT1;
		if ($type == QuarkField::TYPE_DATE) return self::TYPE_DATETIME;

		return self::TYPE_TEXT;
	}
}