<?php
namespace Quark\DataProviders;

use Quark\IQuarkDataProvider;
use Quark\IQuarkModel;

use Quark\Quark;
use Quark\QuarkURI;
use Quark\QuarkModel;
use Quark\QuarkKeyValuePair;
use Quark\QuarkArchException;
use Quark\QuarkConnectionException;

use MongoDB\Driver\Manager;
use MongoDB\Driver\Cursor;
use MongoDB\Driver\Command;
use MongoDB\Driver\Query;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\WriteConcern;
use MongoDB\Driver\WriteResult;
use MongoDB\BSON\ObjectID;

/**
 * Class MongoDBNew
 *
 * TODO: add ability to suppress checking of ObjectId validity for queries and incoming data
 *
 * http://php.net/manual/en/mongodb-driver-manager.executecommand.php
 * http://php.net/manual/ru/mongodb-driver-manager.executebulkwrite.php
 * http://veselov.sumy.ua/2006-novyy-drayver-mongodb-dlya-php-chernovik-po-osnovnym-zaprosam.html
 *
 * @package Quark\DataProviders
 */
class MongoDBNew implements IQuarkDataProvider {
	const WRITE_CONCERN_LEVEL = WriteConcern::MAJORITY;
	const WRITE_CONCERN_TIMEOUT = 100;

	const OPTION_UPDATE_WITH_ID = '___mongodb___update_with_id___';
	const OPTION_COUNT_HINT = '___mongodb___count_hint___';

	/**
	 * @var Manager $_connection
	 */
	private $_connection;

	/**
	 * @var QuarkURI $_uri
	 */
	private $_uri;

	/**
	 * @param $id
	 *
	 * @return bool
	 */
	public static function IsValidId ($id) {
		try {
			return (bool)new ObjectID($id);
		}
		catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * @return Cursor
	 */
	public function Ping () {
		return $this->_connection->executeCommand($this->DBName(), new Command(array('ping' => 1)));
	}

	/**
	 * @return mixed
	 */
	public function DBName () {
		return str_replace('/', '', $this->_uri->path);
	}

	/**
	 * @param string $collection = ''
	 *
	 * @return string
	 */
	public function Target ($collection = '') {
		return $this->DBName() . '.' . $collection;
	}

	/**
	 * @param IQuarkModel $model
	 * @param BulkWrite $query
	 * @param array $options = []
	 * @param WriteConcern $concern = null
	 *
	 * @return WriteResult
	 *
	 * @throws QuarkArchException
	 */
	public function BulkWrite (IQuarkModel $model, BulkWrite $query, $options = [], WriteConcern $concern = null) {
		if (!($concern instanceof WriteConcern))
			$concern = new WriteConcern(self::WRITE_CONCERN_LEVEL, self::WRITE_CONCERN_TIMEOUT);

		try {
			return $this->_connection->executeBulkWrite($this->_collection($model, $options), $query, $concern);
		}
		catch (\Exception $e) {
			throw new QuarkArchException('[MongoDB::BulkWrite] Error during writing model ' . get_class($model) . ': ' . print_r($e, true));
		}
	}

	/**
	 * @param IQuarkModel $model
	 * @param array $query = []
	 * @param array $options = []
	 *
	 * @return Cursor
	 */
	public function Query (IQuarkModel $model, $query = [], $options = []) {
		try {
			return $this->_connection->executeQuery(
				$this->_collection($model, $options),
				new Query($query, $options)
			);
		}
		catch (\Exception $e) {
			Quark::Log('[MongoDB::Query] Can not proceed query on model ' . get_class($model) . ': ' . print_r($e, true));
			return null;
		}
	}

	/**
	 * @param array $command = []
	 *
	 * @return Cursor
	 */
	public function Command ($command = []) {
		try {
			return $this->_connection->executeCommand($this->DBName(), new Command($command));
		}
		catch (\Exception $e) {
			Quark::Log('[MongoDB::Command] Can not proceed command: ' . print_r($e, true));
			return null;
		}
	}

	/**
	 * @param IQuarkModel $model
	 * @param $options
	 * @param bool $full = true
	 *
	 * @return mixed
	 *
	 * @throws QuarkArchException
	 */
	private function _collection ($model, $options, $full = true) {
		$collection = QuarkModel::CollectionName($model, $options);

		return $full ? $this->Target($collection) : $collection;
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return mixed
	 */
	private function _data (IQuarkModel $model) {
		return json_decode(json_encode($model));
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return bool
	 */
	private function _checkId (IQuarkModel $model) {
		if (isset($model->_id)) return true;

		Quark::Log('[MongoDB::_checkId] Model ' . get_class($model) . ' does not have an "_id" primary key');
		return false;
	}

	/**
	 * @param QuarkURI $uri
	 *
	 * @return void
	 *
	 * @throws QuarkConnectionException
	 */
	public function Connect (QuarkURI $uri) {
		$this->_uri = $uri;

		try {
			$this->_connection = new Manager($uri->URI());
			$this->Ping();
		}
		catch (\Exception $e) {
			throw new QuarkConnectionException($uri, Quark::LOG_FATAL, print_r($e, true));
		}
	}

	/**
	 * @param IQuarkModel $model
	 * @param $options
	 *
	 * @return mixed
	 *
	 * @throws QuarkArchException
	 */
	public function Create (IQuarkModel $model, $options = []) {
		if (isset($model->_id))
			unset($model->_id);

		$query = new BulkWrite();
		$model->_id = $query->insert($this->_data($model));

		return $this->BulkWrite($model, $query, $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $options
	 *
	 * @return mixed
	 *
	 * @throws QuarkArchException
	 */
	public function Save (IQuarkModel $model, $options = []) {
		if (!$this->_checkId($model)) return false;

		$data = $this->_data($model);
		/** @noinspection PhpUndefinedFieldInspection */
		$data->_id = $model->_id;

		$query = new BulkWrite();
		$query->update(array('_id' => $data->_id), $data, $options);

		return $this->BulkWrite($model, $query, $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Remove (IQuarkModel $model, $options = []) {
		if (!$this->_checkId($model)) return false;

		$query = new BulkWrite();
		/** @noinspection PhpUndefinedFieldInspection */
		$query->delete(array('_id' => $model->_id), $options);

		return $this->BulkWrite($model, $query, $options);
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return QuarkKeyValuePair
	 */
	public function PrimaryKey (IQuarkModel $model) {
		/** @noinspection PhpParamsInspection */
		return new QuarkKeyValuePair('_id', new ObjectId());
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return array
	 */
	public function Find (IQuarkModel $model, $criteria, $options) {
		$cursor = $this->_connection->executeQuery(
			$this->_collection($model, $options),
			new Query($criteria, $options)
		);

		$out = array();

		foreach ($cursor as $document)
			$out[] = $document;

		return $out;
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return mixed
	 */
	public function FindOne (IQuarkModel $model, $criteria, $options) {
		$options[QuarkModel::OPTION_LIMIT] = 1;

		$out = $this->Find($model, $criteria, $options);

		return sizeof($out) == 0 ? null : $out[0];
	}

	/**
	 * @param IQuarkModel $model
	 * @param $id
	 * @param $options
	 *
	 * @return mixed
	 */
	public function FindOneById (IQuarkModel $model, $id, $options) {
		return self::IsValidId($id)
			? $this->FindOne($model, array('_id' => new ObjectID($id)), $options)
			: null;
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Update (IQuarkModel $model, $criteria, $options) {
		$data = $this->_data($model);

		if (isset($options[self::OPTION_UPDATE_WITH_ID]) && $options[self::OPTION_UPDATE_WITH_ID])
			/** @noinspection PhpParamsInspection */
			$data->_id = isset($model->_id) ? $model->_id : new ObjectID();

		$query = new BulkWrite();
		$query->update($criteria, $model, $options);

		return $this->BulkWrite($model, $query, $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Delete (IQuarkModel $model, $criteria, $options) {
		$query = new BulkWrite();
		$query->delete($criteria, $options);

		return $this->BulkWrite($model, $query, $options);
	}

	/**
	 * http://php.net/manual/ru/class.mongodb-driver-cursor.php#120922
	 * https://stackoverflow.com/a/41920343/2097055
	 * https://docs.mongodb.com/manual/reference/command/count/#dbcmd.count
	 *
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $limit
	 * @param $skip
	 * @param $options
	 *
	 * @return int
	 */
	public function Count (IQuarkModel $model, $criteria, $limit, $skip, $options) {
		$command = array(
			'count' => $this->_collection($model, $options, false),
			'query' => $criteria,
			'skip' => $skip,
			'limit' => $limit,
		);

		if (isset($options[QuarkModel::OPTION_SKIP]))
			$command['skip'] = $options[QuarkModel::OPTION_SKIP];

		if (isset($options[QuarkModel::OPTION_LIMIT]))
			$command['limit'] = $options[QuarkModel::OPTION_LIMIT];

		if (isset($options[self::OPTION_COUNT_HINT]))
			$command['hint'] = $options[self::OPTION_COUNT_HINT];

		$result = $this->Command($command)->toArray();

		return is_array($result) && isset($result[0]->n) ? $result[0]->n : 0;
	}
}