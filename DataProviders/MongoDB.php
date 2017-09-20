<?php
namespace Quark\DataProviders;

use Quark\IQuarkDataProvider;
use Quark\IQuarkModel;

use Quark\Quark;
use Quark\QuarkDate;
use Quark\QuarkKeyValuePair;
use Quark\QuarkArchException;
use Quark\QuarkModel;
use Quark\QuarkURI;
use Quark\QuarkConnectionException;

use MongoDB\Driver\Manager;
use MongoDB\Driver\Cursor;
use MongoDB\Driver\Command;
use MongoDB\Driver\Query;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\WriteConcern;
use MongoDB\Driver\WriteResult;
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\Regex;

/**
 * Interface IQuarkMongoDBDriver
 *
 * @package Quark\DataProviders
 */
interface IQuarkMongoDBDriver {
	/**
	 * @param $id
	 *
	 * @return bool
	 */
	public static function IsValidId($id);

	/**
	 * @param $id
	 *
	 * @return string
	 */
	public static function _id($id);

	/**
	 * @param QuarkDate $date = null
	 *
	 * @return \MongoId|ObjectID
	 */
	public static function IdOfDate(QuarkDate $date);

	/**
	 * @param \MongoId|ObjectID $id = null
	 *
	 * @return QuarkDate
	 */
	public static function DateOfId($id);

	/**
	 * @param string $regex
	 *
	 * @return array
	 */
	public static function QueryRegex($regex);

	/**
	 * @param QuarkURI $uri
	 *
	 * @return mixed
	 */
	public function Connect(QuarkURI $uri);

	/**
	 * @param IQuarkModel $model
	 * @param $options = []
	 *
	 * @return mixed
	 */
	public function Create(IQuarkModel $model, $options = []);

	/**
	 * @param IQuarkModel $model
	 * @param $options = []
	 *
	 * @return mixed
	 */
	public function Save(IQuarkModel $model, $options = []);

	/**
	 * @param IQuarkModel $model
	 * @param $options = []
	 *
	 * @return mixed
	 */
	public function Remove(IQuarkModel $model, $options = []);

	/**
	 * @param IQuarkModel $model
	 *
	 * @return QuarkKeyValuePair
	 */
	public function PrimaryKey (IQuarkModel $model);

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options = []
	 *
	 * @return array
	 */
	public function Find(IQuarkModel $model, $criteria, $options = []);

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options = []
	 *
	 * @return mixed
	 */
	public function FindOne(IQuarkModel $model, $criteria, $options = []);

	/**
	 * @param IQuarkModel $model
	 * @param $id
	 * @param $options = []
	 *
	 * @return mixed
	 */
	public function FindOneById(IQuarkModel $model, $id, $options = []);

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options = []
	 *
	 * @return mixed
	 */
	public function Update(IQuarkModel $model, $criteria, $options = []);

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options = []
	 *
	 * @return mixed
	 */
	public function Delete(IQuarkModel $model, $criteria, $options = []);

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $limit
	 * @param $skip
	 * @param $options = []
	 *
	 * @return int
	 */
	public function Count (IQuarkModel $model, $criteria, $limit, $skip, $options = []);
}

/**
 * Class MongoDB
 *
 * @package Quark\DataProviders
 */
class MongoDB implements IQuarkDataProvider {
	const LEGACY = '__legacy';

	/**
	 * @var IQuarkMongoDBDriver $_driver
	 */
	private $_driver;

	/**
	 * @var string $_driverClass = ''
	 */
	private static $_driverClass = '';

	/**
	 * @param string $method = ''
	 * @param array $args = []
	 *
	 * @return mixed
	 */
	private static function _callStatic ($method = '', $args = []) {
		return call_user_func_array(array(self::$_driverClass, $method), $args);
	}

	/**
	 * @param $id = null
	 *
	 * @return string
	 */
	public static function _id ($id = null) {
		return $id == null ? null : self::_callStatic('_id', array($id));
	}

	/**
	 * @param QuarkDate $date = null
	 *
	 * @return \MongoId|ObjectID
	 */
	public static function IdOfDate (QuarkDate $date = null) {
		return $date == null ? null : self::_callStatic('IdOfDate', array($date));
	}

	/**
	 * @param \MongoId|ObjectID $id = null
	 *
	 * @return QuarkDate
	 */
	public static function DateOfId ($id = null) {
		return $id == null ? null : self::_callStatic('DateOfId', array($id));
	}

	/**
	 * @param string $mod = ''
	 * @param QuarkDate $date = ''
	 * @param string $key = '_id'
	 *
	 * @return array
	 *
	 * @throws QuarkArchException
	 */
	public static function QueryByCreationDate ($mod = '$eq', QuarkDate $date = null, $key = '_id') {
		if (!is_string($mod))
			throw new QuarkArchException('[MongoDB::QueryByCreationDate] Illegal modifier. Expected one of $lt, $lte, $eq (since MongoDB v3.0), $gte, $gt, got (' . gettype($mod) . ') ' . print_r($mod, true));

		return array($key => array($mod => self::IdOfDate($date)));
	}

	/**
	 * @param string $regex
	 *
	 * @return array
	 */
	public static function QueryRegex ($regex) {
		return self::_callStatic('QueryRegex', array($regex));
	}

	/**
	 * @param QuarkURI $uri
	 *
	 * @return mixed
	 */
	public function Connect (QuarkURI $uri) {
		$legacy = $uri->Options(self::LEGACY);

		if ($legacy === null) $this->_driver = new _MongoDB_php_mongodb();
		else {
			$uri->RemoveOption(self::LEGACY);
			$this->_driver = new _MongoDB_php_mongo();
		}

		$this->_driver->Connect($uri);

		self::$_driverClass = get_class($this->_driver);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $options = []
	 *
	 * @return mixed
	 */
	public function Create (IQuarkModel $model, $options = []) {
		return $this->_driver->Create($model, $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $options = []
	 *
	 * @return mixed
	 */
	public function Save (IQuarkModel $model, $options = []) {
		return $this->_driver->Save($model, $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $options = []
	 *
	 * @return mixed
	 */
	public function Remove (IQuarkModel $model, $options = []) {
		return $this->_driver->Remove($model, $options);
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return QuarkKeyValuePair
	 */
	public function PrimaryKey (IQuarkModel $model) {
		return $this->_driver->PrimaryKey($model);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options = []
	 *
	 * @return array
	 */
	public function Find (IQuarkModel $model, $criteria, $options = []) {
		return $this->_driver->Find($model, $criteria, $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options = []
	 *
	 * @return mixed
	 */
	public function FindOne (IQuarkModel $model, $criteria, $options = []) {
		return $this->_driver->FindOne($model, $criteria, $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $id
	 * @param $options = []
	 *
	 * @return mixed
	 */
	public function FindOneById (IQuarkModel $model, $id, $options = []) {
		return $this->_driver->FindOneById($model, $id, $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options = []
	 *
	 * @return mixed
	 */
	public function Update (IQuarkModel $model, $criteria, $options = []) {
		return $this->_driver->Update($model, $criteria, $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options = []
	 *
	 * @return mixed
	 */
	public function Delete (IQuarkModel $model, $criteria, $options = []) {
		return $this->_driver->Delete($model, $criteria, $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $limit
	 * @param $skip
	 * @param $options = []
	 *
	 * @return int
	 */
	public function Count (IQuarkModel $model, $criteria, $limit, $skip, $options = []) {
		return $this->_driver->Count($model, $criteria, $limit, $skip, $options);
	}
}

/**
 * Class _MongoDB_php_mongo
 *
 * @package Quark\DataProviders
 */
class _MongoDB_php_mongo implements IQuarkMongoDBDriver {
	/**
	 * @var \MongoDb $_connection
	 */
	private $_connection;

	/**
	 * @param $id
	 *
	 * @return bool
	 */
	public static function IsValidId ($id) {
		try {
			return (bool)new \MongoId($id);
		}
		catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * @param $source
	 *
	 * @return string
	 */
	public static function _id ($source) {
		if (self::IsValidId($source)) return (string)$source;

		if (is_array($source))
			$source = (object)$source;

		if (isset($source->_id)) {
			if (is_array($source->_id))
				$source->_id = (object)$source->_id;

			if (self::IsValidId($source->_id))
				return (string)$source->_id;
		}

		if (isset($source->{'$id'}) && self::IsValidId($source->{'$id'}))
			return (string)$source->{'$id'};

		if (isset($source->_id->{'$id'}) && self::IsValidId($source->_id->{'$id'}))
			return (string)$source->_id->{'$id'};

		return '';
	}

	/**
	 * http://stackoverflow.com/a/13594408/2097055
	 *
	 * @param QuarkDate $date
	 *
	 * @return \MongoId
	 */
	public static function IdOfDate (QuarkDate $date) {
		return new \MongoId(base_convert($date->Timestamp(), 10, 16) . '0000000000000000');
	}

	/**
	 * https://steveridout.github.io/mongo-object-time/
	 *
	 * @param \MongoId $id
	 *
	 * @return QuarkDate
	 */
	public static function DateOfId ($id) {
		if (!($id instanceof \MongoId)) return null;

		$date = substr((string)$id, 0, 8);
		if ($date == false) return null;

		return QuarkDate::FromTimestamp(base_convert($date, 16, 10));
	}

	/**
	 * @param string $regex
	 *
	 * @return array
	 */
	public static function QueryRegex ($regex) {
		return array(
			'$regex' => new \MongoRegex($regex)
		);
	}

	/**
	 * @param IQuarkModel $model
	 * @param bool $_id
	 *
	 * @return mixed
	 */
	private function _data (IQuarkModel $model, $_id = true) {
		$out = json_decode(json_encode($model));

		if ($_id) $out->_id = new \MongoId(self::_id($model));
		else unset($out->_id);

		return $out;
	}

	/**
	 * @param array $source
	 * @param array $exclude
	 *
	 * @return array
	 */
	public static function _ids ($source = [], $exclude = []) {
		if (!is_array($source)) return array();

		if (is_string($exclude))
			$exclude = array($exclude);

		if (!is_array($exclude))
			$exclude = array();

		$ids = array();

		foreach ($source as $id)
			if (self::IsValidId($id) && !in_array($id, $exclude, true))
				$ids[] = new \MongoId($id);

		return $ids;
	}

	/**
	 * @return bool
	 */
	public static function CompareIds () {
		$ids = func_get_args();

		if (sizeof($ids) == 0) return true;

		$prev = $ids[0];
		$out = true;

		foreach ($ids as $id) {
			$out = $out && self::_id($id) == self::_id($prev);
			$prev = $id;
		}

		return $out;
	}

	/**
	 * @param QuarkURI $uri
	 *
	 * @return void
	 *
	 * @throws QuarkConnectionException
	 */
	public function Connect (QuarkURI $uri) {
		try {
			$connection = new \MongoClient($uri->URI(), $uri->Options());
			$uri->path = str_replace('/', '', $uri->path);

			if (strlen(trim($uri->path)) != 0) {
				$db = $uri->path;
				$this->_connection = $connection->$db;
			}
		}
		catch (\Exception $e) {
			throw new QuarkConnectionException($uri, Quark::LOG_FATAL);
		}
	}

	/**
	 * @param IQuarkModel $model
	 * @param $options
	 *
	 * @return mixed
	 * @throws QuarkArchException
	 */
	private function _collection ($model ,$options) {
		if ($this->_connection == null)
			throw new QuarkArchException('MongoDB connection not pooled');

		$collection = QuarkModel::CollectionName($model, $options);
		return $this->_connection->$collection;
	}

	/**
	 * @param IQuarkModel|\stdClass $model
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Create (IQuarkModel $model, $options = []) {
		$data = $this->_data($model, false);
		$out = $this->_collection($model, $options)->insert($data, $options);

		$model->_id = $data->_id;

		return $out;
	}

	/**
	 * @param IQuarkModel $model
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Save (IQuarkModel $model, $options = []) {
		return $this->_collection($model, $options)->save($this->_data($model), $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Remove (IQuarkModel $model, $options = []) {
		if (!isset($model->_id)) return false;

		return $this->_collection($model, $options)->remove(array(
			'_id' => new \MongoId(self::_id($model))
		), $options);
	}

	/**
	 * @param $options
	 *
	 * @return array
	 */
	private static function _fields ($options) {
		return isset($options[QuarkModel::OPTION_FIELDS]) && is_array($options[QuarkModel::OPTION_FIELDS])
			? $options[QuarkModel::OPTION_FIELDS]
			: array();
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	private static function _record ($raw) {
		return is_array($raw) || is_object($raw) ? $raw : null;
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return QuarkKeyValuePair
	 */
	public function PrimaryKey (IQuarkModel $model) {
		return new QuarkKeyValuePair('_id', new \MongoId());
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return array
	 */
	public function Find (IQuarkModel $model, $criteria, $options = []) {
		/**
		 * @var \MongoCursor $raw
		 */
		$raw = $this->_collection($model, $options)->find($criteria, self::_fields($options));

		if (isset($options[QuarkModel::OPTION_SORT]))
			$raw->sort($options[QuarkModel::OPTION_SORT]);

		if (isset($options[QuarkModel::OPTION_LIMIT]))
			$raw->limit($options[QuarkModel::OPTION_LIMIT]);

		if (isset($options[QuarkModel::OPTION_SKIP]))
			$raw->skip($options[QuarkModel::OPTION_SKIP]);

		$buffer = array();
		$item = null;

		foreach ($raw as $document) {
			/**
			 * @var \stdClass $document->_id
			 */
			$item = $document;

			if (isset($options['getId']) && $options['getId'] == true)
				$item->_id = $document->_id->{'$id'};

			$buffer[] = self::_record($item);
		}

		return $buffer;
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return mixed
	 */
	public function FindOne (IQuarkModel $model, $criteria, $options = []) {
		/**
		 * @var \MongoCursor $raw
		 */
		$raw = $this->_collection($model, $options)->find($criteria, self::_fields($options))->limit(1);

		if (isset($options[QuarkModel::OPTION_SORT]))
			$raw->sort($options[QuarkModel::OPTION_SORT]);

		foreach ($raw as $document)
			return self::_record($document);

		return null;
	}

	/**
	 * @param IQuarkModel $model
	 * @param $id
	 * @param $options
	 *
	 * @return mixed
	 */
	public function FindOneById (IQuarkModel $model, $id, $options = []) {
		$id = self::_id($id);

		if (!self::IsValidId($id)) return null;

		return self::_record($this->_collection($model, $options)->findOne(array(
			'_id' => new \MongoId($id)
		), self::_fields($options)));
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Update (IQuarkModel $model, $criteria, $options = []) {
		return $this->_collection($model, $options)->update($criteria, $this->_data($model, false), $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Delete (IQuarkModel $model, $criteria, $options = []) {
		return $this->_collection($model, $options)->remove($criteria, $options);
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
		return $this->_collection($model, $options)->count($criteria, sizeof($options) != 0 ? $options : null);
	}
}


/**
 * Class _MongoDB_php_mongodb
 *
 * TODO: add ability to suppress checking of ObjectId validity for queries and incoming data
 *
 * http://php.net/manual/en/mongodb-driver-manager.executecommand.php
 * http://php.net/manual/ru/mongodb-driver-manager.executebulkwrite.php
 * http://veselov.sumy.ua/2006-novyy-drayver-mongodb-dlya-php-chernovik-po-osnovnym-zaprosam.html
 *
 * @package Quark\DataProviders
 */
class _MongoDB_php_mongodb implements IQuarkMongoDBDriver {
	const PARAM_WRITE_CONCERN_LEVEL = '__writeConcernLevel';
	const PARAM_WRITE_CONCERN_TIMEOUT = '__writeConcernTimeout';
	const PARAM_LOG = '__log';

	const WRITE_CONCERN_LEVEL_MAJORITY = WriteConcern::MAJORITY;
	const WRITE_CONCERN_LEVEL_STANDALONE = 1;
	const WRITE_CONCERN_LEVEL_DISABLED = 0;
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
	 * @var string $_writeConcernLevel = self::WRITE_CONCERN_LEVEL_MAJORITY
	 */
	private $_writeConcernLevel = self::WRITE_CONCERN_LEVEL_MAJORITY;

	/**
	 * @var int $_writeConcernTimeout = self::WRITE_CONCERN_TIMEOUT
	 */
	private $_writeConcernTimeout = self::WRITE_CONCERN_TIMEOUT;

	/**
	 * @var bool $_log = false
	 */
	private $_log = false;

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
	 * @param $source
	 *
	 * @return string
	 */
	public static function _id ($source) {
		if (self::IsValidId($source)) return (string)$source;

		if (is_array($source))
			$source = (object)$source;

		if (isset($source->_id)) {
			if (is_array($source->_id))
				$source->_id = (object)$source->_id;

			if (self::IsValidId($source->_id))
				return (string)$source->_id;
		}

		if (isset($source->{'$oid'}) && self::IsValidId($source->{'$oid'}))
			return (string)$source->{'$oid'};

		if (isset($source->_id->{'$oid'}) && self::IsValidId($source->_id->{'$oid'}))
			return (string)$source->_id->{'$oid'};

		return '';
	}

	/**
	 * @param QuarkDate $date
	 *
	 * @return ObjectID
	 */
	public static function IdOfDate (QuarkDate $date) {
		return new ObjectID(base_convert($date->Timestamp(), 10, 16) . '0000000000000000');
	}

	/**
	 * @param string $regex
	 *
	 * @return array
	 */
	public static function QueryRegex ($regex) {
		if (!preg_match('#(.){1}(.*)\1([a-zA-Z]*)#is', $regex, $found)) return null;

		return array(
			'$regex' => new Regex($found[2], $found[3])
		);
	}

	/**
	 * @param ObjectID $id
	 *
	 * @return QuarkDate
	 */
	public static function DateOfId ($id) {
		if (!($id instanceof ObjectID)) return null;

		$date = substr((string)$id, 0, 8);
		if ($date == false) return null;

		return QuarkDate::FromTimestamp(base_convert($date, 16, 10));
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
			$concern = new WriteConcern($this->_writeConcernLevel, $this->_writeConcernTimeout);

		try {
			return $this->_connection->executeBulkWrite($this->_collection($model, $options), $query, $concern);
		}
		catch (\Exception $e) {
			throw new QuarkArchException('[MongoDB::BulkWrite] Error during writing model ' . get_class($model) . ': ' . $e->getMessage() . ($this->_log ? ' - ' . print_r($e, true) : ''));
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
			Quark::Log('[MongoDB::Query] Can not proceed query on model ' . get_class($model) . ': ' . $e->getMessage() . ($this->_log ? ' - ' . print_r($e, true) : ''));
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
			Quark::Log('[MongoDB::Command] Can not proceed command: ' . $e->getMessage() . ($this->_log ? ' - ' . print_r($e, true) : ''));
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
		$level = $uri->Options(self::PARAM_WRITE_CONCERN_LEVEL);

		if ($level !== null) {
			$uri->RemoveOption(self::PARAM_WRITE_CONCERN_LEVEL);

			if (is_numeric($level))
				$level = (int)$level;

			$this->_writeConcernLevel = $level;
		}

		$timeout = $uri->Options(self::PARAM_WRITE_CONCERN_TIMEOUT);

		if ($timeout !== null) {
			$uri->RemoveOption(self::PARAM_WRITE_CONCERN_TIMEOUT);

			$this->_writeConcernTimeout = (int)$timeout;
		}

		$log = $uri->Options(self::PARAM_LOG);

		if ($log !== null) {
			$uri->RemoveOption(self::PARAM_LOG);

			$this->_log = $log != 'false';
		}

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
		$query = new BulkWrite();

		if (isset($model->_id))
			unset($model->_id);

		/** @noinspection PhpUndefinedFieldInspection */
		$model->_id = $query->insert($model);

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
	 * @param $options = []
	 *
	 * @return array
	 */
	public function Find (IQuarkModel $model, $criteria, $options = []) {
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
	 * @param $options = []
	 *
	 * @return mixed
	 */
	public function FindOne (IQuarkModel $model, $criteria, $options = []) {
		$options[QuarkModel::OPTION_LIMIT] = 1;

		$out = $this->Find($model, $criteria, $options);

		return sizeof($out) == 0 ? null : $out[0];
	}

	/**
	 * @param IQuarkModel $model
	 * @param $id
	 * @param $options = []
	 *
	 * @return mixed
	 */
	public function FindOneById (IQuarkModel $model, $id, $options = []) {
		$id = self::_id($id);

		return self::IsValidId($id)
			? $this->FindOne($model, array('_id' => new ObjectID($id)), $options)
			: null;
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options = []
	 *
	 * @return mixed
	 */
	public function Update (IQuarkModel $model, $criteria, $options = []) {
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
	 * @param $options = []
	 *
	 * @return mixed
	 */
	public function Delete (IQuarkModel $model, $criteria, $options = []) {
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
	 * @param $options = []
	 *
	 * @return int
	 */
	public function Count (IQuarkModel $model, $criteria, $limit, $skip, $options = []) {
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