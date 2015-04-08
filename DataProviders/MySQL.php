<?php
namespace Quark\DataProviders;

use Quark\IQuarkModel;
use Quark\IQuarkDataProvider;
use Quark\IQuarkSQLDataProvider;

use Quark\Quark;
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
	/**
	 * @var \mysqli $_connection
	 */
	private $_connection;

	/**
	 * @var QuarkSQL $_sql
	 */
	private $_sql;

	/**
	 * @param QuarkURI $uri
	 *
	 * @return mixed
	 *
	 * @throws QuarkArchException
	 * @throws QuarkConnectionException
	 */
	public function Connect (QuarkURI $uri) {
		$this->_connection = \mysqli_init();

		if (!$this->_connection)
			throw new QuarkArchException('MySQLi initialization fault');

		$options = $uri->options;

		if (is_array($options))
			foreach ($options as $key => $value) {
				if (!$this->_connection->options($key, $value))
					throw new QuarkArchException('MySQLi option set error');
			}

		if (!$this->_connection->real_connect(
			$uri->host,
			$uri->user,
			$uri->pass,
			QuarkSQL::DBName($uri->path),
			(int)$uri->port
		))
			throw new QuarkConnectionException($uri, Quark::LOG_FATAL);

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
	 * @param             $criteria
	 * @param array       $options
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
	 * @param             $criteria
	 * @param array       $options
	 *
	 * @return mixed
	 */
	public function FindOne (IQuarkModel $model, $criteria, $options = []) {
		$records = $this->Find($model, $criteria, $options + array(QuarkModel::OPTION_LIMIT => 1));

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
		$result = $this->_sql->Count($model, $criteria, $options + array(
				QuarkModel::OPTION_FIELDS => array(QuarkSQL::FIELD_COUNT_ALL),
				QuarkModel::OPTION_SKIP => $skip,
				QuarkModel::OPTION_LIMIT => $limit
			));

		return !$result ? 0 : (int)$result->fetch_row()[0];
	}

	/**
	 * @param string $query
	 * @param array  $options
	 *
	 * @return mixed
	 */
	public function Query ($query, $options) {
		$mode = isset($options['mode'])
			? $options['mode']
			: MYSQLI_STORE_RESULT;

		return $this->_connection->query($query, $mode);
	}

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	public function Escape ($value) {
		return $this->_connection->real_escape_string($value);
	}

	/**
	 * @return string
	 */
	public function EscapeChar () {
		return '`';
	}
}