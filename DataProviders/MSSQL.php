<?php
namespace Quark\DataProviders;

use Quark\IQuarkDataProvider;
use Quark\IQuarkSQLDataProvider;
use Quark\IQuarkModel;

use Quark\Quark;
use Quark\QuarkArchException;
use Quark\QuarkConnectionException;
use Quark\QuarkException;
use Quark\QuarkField;
use Quark\QuarkKeyValuePair;
use Quark\QuarkModel;
use Quark\QuarkSQL;
use Quark\QuarkURI;

/**
 * Class MSSQL
 *
 * @package Quark\DataProviders
 */
class MSSQL implements IQuarkDataProvider, IQuarkSQLDataProvider {
	const SCHEME_LOCAL_DB = 'ms-sql-local-db';
	const SCHEME_TCP = 'ms-sql-tcp';

	const DEFAULT_PORT = 1433;

	const CONNECTION_OPTION_DATABASE = 'Database';
	const CONNECTION_OPTION_USERNAME = 'UID';
	const CONNECTION_OPTION_PASSWORD = 'PWD';

	const OPTION_QUERY_FREE = '___query_free___';

	/**
	 * @var resource $_connection
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
	 * @param IQuarkModel $model
	 * @param array $options
	 *
	 * @return array
	 */
	private function _options (IQuarkModel $model, $options) {
		if (!isset($options[QuarkModel::OPTION_SORT])) {
			$pk = $this->PrimaryKey($model)->Key();
			$options[QuarkModel::OPTION_SORT] = array($pk => QuarkModel::SORT_ASC);
		}

		return $options;
	}

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

		if (!function_exists('sqlsrv_connect'))
			throw new QuarkConnectionException($uri, Quark::LOG_FATAL, '[MSSQL] Connection error: this PHP installation does not have configured MSSQL extension (php_sqlsrv)');

		$connect = '';

		switch ($uri->scheme) {
			case self::SCHEME_LOCAL_DB: $connect = '(localdb)\\' . $uri->host; break;
			case self::SCHEME_TCP: $connect = 'tcp:' . $uri->host . ', ' . $uri->port; break;
			default: break;
		}

		if ($connect == '')
			throw new QuarkConnectionException($uri, Quark::LOG_FATAL, '[MSSQL] Connection error: unresolved connection string');

		$options = $uri->Options();

		if (!isset($options[self::CONNECTION_OPTION_DATABASE]))
			$options[self::CONNECTION_OPTION_DATABASE] = QuarkSQL::DBName($uri->path);

		if (!isset($options[self::CONNECTION_OPTION_USERNAME]))
			$options[self::CONNECTION_OPTION_USERNAME] = $uri->user;

		if (!isset($options[self::CONNECTION_OPTION_PASSWORD]))
			$options[self::CONNECTION_OPTION_PASSWORD] = $uri->pass;

		if (!$this->_connection = \sqlsrv_connect($connect, $options))
			throw new QuarkConnectionException($uri, Quark::LOG_FATAL, QuarkException::LastError());

		$this->_sql = new QuarkSQL($this);
	}

	/**
	 * @param IQuarkModel $model
	 * @param array $options = []
	 *
	 * @return mixed
	 */
	public function Create (IQuarkModel $model, $options = []) {
		$epk = false;
		$pk = $this->PrimaryKey($model)->Key();

		if (!isset($options[QuarkSQL::OPTION_QUERY_REVIEWER])) {
			$epk = true;
			unset($model->$pk);

			$options[QuarkSQL::OPTION_QUERY_REVIEWER] = function ($query) use ($pk) {
				return preg_replace('#^INSERT INTO (.+) \((.*)\) VALUES (.*)#Uis', 'INSERT INTO $1 ($2) OUTPUT INSERTED.* VALUES $3', $query);
			};
		}

		$out = $this->_sql->Insert($model, $options);

		if ($epk && $out) {
			$insert = \sqlsrv_fetch_array($out);
			$model->$pk = $insert[0];
		}

		return $out;
	}

	/**
	 * @param IQuarkModel $model
	 * @param array $options = []
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
	 * @param array $options = []
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
		return $this->_sql->Pk($model, 'Id', 0);
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param             $options
	 *
	 * @return array
	 */
	public function Find (IQuarkModel $model, $criteria, $options) {
		$output = array();
		$records = $this->_sql->Select($model, $criteria, $this->_options($model, $options));

		if ($records) {
			while ($record = \sqlsrv_fetch_array($records, SQLSRV_FETCH_ASSOC))
				$output[] = $record;

			\sqlsrv_free_stmt($records);
		}

		return $output;
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param             $options
	 *
	 * @return mixed
	 */
	public function FindOne (IQuarkModel $model, $criteria, $options) {
		$options = $this->_options($model, $options);
		$records = $this->Find($model, $criteria, array_merge($options, array(QuarkModel::OPTION_LIMIT => 1)));

		return sizeof($records) == 0 ? null : $records[0];
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $id
	 * @param             $options
	 *
	 * @return mixed
	 */
	public function FindOneById (IQuarkModel $model, $id, $options) {
		return $this->FindOne($model, array(
			$this->PrimaryKey($model)->Key() => $id
		), $this->_options($model, $options));
	}

	/**
	 * TODO: implementing subquery approach
	 * http://stackoverflow.com/a/655021/2097055
	 *
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
	 * TODO: implementing subquery approach
	 * http://stackoverflow.com/a/655021/2097055
	 *
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
	public function Count (IQuarkModel $model, $criteria, $limit, $skip, $options) {
		$options = $this->_options($model, $options);
		$result = $this->_sql->Count($model, $criteria, array_merge($options, array(
				QuarkSQL::OPTION_FIELDS => 'COUNT(1) OVER()',
				//QuarkSQL::OPTION_FIELDS => 'COUNT(1) OVER(ORDER BY Id)',
				//QuarkSQL::OPTION_FIELDS => 'ROW_NUMBER() OVER(ORDER BY [Id] ASC) AS RowNum',
				//QuarkSQL::OPTION_FIELDS => '*, COUNT(*) OVER()',
				QuarkModel::OPTION_SKIP => $skip,
				QuarkModel::OPTION_LIMIT => $limit// == 0 ? 1 : $limit
			)));

		return !$result ? 0 : (int)\sqlsrv_fetch_array($result)[0];
	}

	/**
	 * @param string $query
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function Query ($query, $options) {
		$skip = false;

		if (isset($options[QuarkModel::OPTION_SKIP])) {
			$skip = true;
			$query = preg_replace('#(LIMIT [0-9]+\s)?(OFFSET [0-9]+)?$#Uis', '$2', $query) . ' ROWS';
		}

		if (isset($options[QuarkModel::OPTION_LIMIT])) {
			$pattern = '#(LIMIT [0-9]+)?\s?(OFFSET [0-9]+ ROWS)?$#is';
			$limit = (int)$options[QuarkModel::OPTION_LIMIT];

			if ($skip) $query = preg_replace($pattern, '$0', $query) . ' FETCH NEXT ' . $limit . ' ROWS ONLY';
			else {
				$query = preg_replace($pattern, '', $query);
				$query = preg_replace('#^([a-zA-Z]+) #is', '$1 TOP ' . $limit . ' ', $query);
			}
		}

		if (!isset($options[self::OPTION_QUERY_FREE]))
			$options[self::OPTION_QUERY_FREE] = false;

		$hash = Quark::GuID();
		$set = '';

		$query = preg_replace_callback('#SET (\[[a-zA-Z0-9_]+\]\s*\=\s*\'?(.*)\'?,?\s?)+( WHERE)?#is', function ($found) use (&$hash, &$set) {
			$set = $found[0];
			return $hash;
		}, $query);

		$query = preg_replace('#(\[[a-zA-Z0-9_]+\])\s*\=\s*\'(.*)\'#Uis', '$1 LIKE \'$2\'', $query);
		$query = str_replace($hash, $set, $query);

		if (isset($options[QuarkSQL::OPTION_QUERY_DEBUG]) && $options[QuarkSQL::OPTION_QUERY_DEBUG])
			Quark::Log('[MSSQL] Query: "' . $query . '"');

		$out = \sqlsrv_query($this->_connection, $query);

		if (!$out)
			Quark::Log('[MSSQL] Query error "' . $query . '". Errors: ' . print_r(\sqlsrv_errors(), true));

		if ($options[self::OPTION_QUERY_FREE] && $out)
			\sqlsrv_free_stmt($out);

		return $out;
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	public function EscapeCollection ($name) {
		return '[' . $name . ']';
	}

	/**
	 * @param string $field
	 *
	 * @return string
	 */
	public function EscapeField ($field) {
		return '[' . $field . ']';
	}

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	public function EscapeValue ($value) {
		return str_replace('\'', "''", $value);
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
	public function GenerateSchema (IQuarkModel $model, $options = []) {
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