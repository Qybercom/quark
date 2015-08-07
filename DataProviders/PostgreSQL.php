<?php
namespace Quark\DataProviders;

use Quark\IQuarkModel;
use Quark\IQuarkDataProvider;
use Quark\IQuarkSQLDataProvider;

use Quark\Quark;
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
	 * @return mixed
	 *
	 * @throws QuarkConnectionException
	 */
	public function Connect (QuarkURI $uri) {
		$this->_connection = \pg_connect(
			'host=\'' . $uri->host . '\'' .
			'port=\'' . $uri->port . '\'' .
			'dbname=\'' . QuarkSQL::DBName($uri->path) . '\'' .
			'user=\'' . $uri->user . '\'' .
			'password=\'' . $uri->pass . '\'' .
			'options=\'' . $uri->options . '\''
		);

		if (!$this->_connection)
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
	 * @return string
	 */
	public function PrimaryKey () {
		return 'id';
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
			QuarkModel::OPTION_LIMIT => $limit == 0 ? 'ALL' : $limit
		));

		if (!$result) return 0;

		$out = pg_fetch_assoc($result);

		return isset($out['count']) ? (int)$out['count'] : 0;
	}

	/**
	 * @param string $query
	 * @param array  $options
	 *
	 * @return mixed
	 */
	public function Query ($query, $options) {
		return \pg_query($this->_connection, $query . ';');
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
}