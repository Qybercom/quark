<?php
namespace Quark\DataProviders;

use Quark\IQuarkDataProvider;
use Quark\IQuarkGuIDSynchronizer;
use Quark\IQuarkModel;
use Quark\IQuarkModelWithCustomPrimaryKey;

use Quark\Quark;
use Quark\QuarkArchException;
use Quark\QuarkCollection;
use Quark\QuarkDTO;
use Quark\QuarkFile;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkKeyValuePair;
use Quark\QuarkModel;
use Quark\QuarkObject;
use Quark\QuarkURI;

/**
 * Class QuarkDNA
 *
 * @package Quark\DataProviders
 */
class QuarkDNA implements IQuarkDataProvider, IQuarkGuIDSynchronizer {
	const OPTION_FORCE_ID = '___dna_force_id___';

	const CONNECTION_EXTERNAL = 'external';

	/**
	 * @var QuarkFile $_storage
	 */
	private $_storage;

	/**
	 * @var \stdClass $_db
	 */
	private $_db;

	/**
	 * @var QuarkURI $_uri
	 */
	private $_uri;

	/**
	 * @param IQuarkModel $model
	 * @param $options
	 *
	 * @return string
	 */
	private function _collection (IQuarkModel $model, $options = []) {
		$collection = QuarkModel::CollectionName($model, $options);

		if (sizeof((array)$this->_db) == 0)
			$this->_db = (object)array($collection => new QuarkCollection(new \stdClass()));

		if (!isset($this->_db->$collection))
			$this->_db->$collection = new QuarkCollection(new \stdClass());

		return $collection;
	}

	/**
	 * @return bool
	 */
	private function _transaction () {
		$db = new \stdClass();
		
		foreach ($this->_db as $name => &$collection)
			/**
			 * @var QuarkCollection $collection
			 */
			$db->$name = $collection->Extract();
			
		$this->_storage->Content(json_encode($db));

		if ($this->_uri->fragment != self::CONNECTION_EXTERNAL)
			return $this->_storage->SaveContent();

		$req = new QuarkDTO();
		$req->Method(QuarkDTO::METHOD_PUT);
		$req->Data($this->_storage);

		$res = QuarkHTTPClient::To($this->_uri, $req, new QuarkDTO(new QuarkJSONIOProcessor()));

		return $res && $res->Status() == QuarkDTO::STATUS_200_OK;
	}

	/**
	 * @param QuarkURI $uri
	 *
	 * @return void
	 *
	 * @throws QuarkArchException
	 */
	public function Connect (QuarkURI $uri) {
		if ($uri->path == null)
			throw new QuarkArchException('QuarkDNA: Database path cannot be empty');

		if ($uri->fragment == self::CONNECTION_EXTERNAL) {
			$this->_storage = QuarkHTTPClient::Download($uri, QuarkDTO::ForGET());

			if ($this->_storage == null)
				throw new QuarkArchException('QuarkDNA: Database does not exists. This usually means that you requested an external database and it was not found');
		}
		else {
			$this->_storage = new QuarkFile($uri->path);

			if ($this->_storage->Exists()) {
				if (!Quark::MemoryAvailable())
					throw new QuarkArchException('QuarkDNA: Database cannot be loaded because of reaching memory limit of ' . Quark::Config()->Alloc() . 'MB set in QuarkConfig::Alloc().');

				$this->_storage->Load();
			}
			else {
				$this->_storage->Content('{}');
				$this->_storage->SaveContent();
			}
		}

		$this->_uri = $uri;

		$db = json_decode($this->_storage->Content());
		
		if ($this->_db == null)
			$this->_db = new \stdClass();
		
		if (QuarkObject::isTraversable($db))
			foreach ($db as $name => &$collection)
				$this->_db->$name = new QuarkCollection(new \stdClass(), $collection);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Create (IQuarkModel $model, $options = []) {
		if (!isset($options[self::OPTION_FORCE_ID]))
			$options[self::OPTION_FORCE_ID] = true;

		$collection = $this->_collection($model);

		$pk = $this->PrimaryKey($model)->Key();
		$model->$pk = $options[self::OPTION_FORCE_ID] ? Quark::GuID() : $model->$pk;
		
		$this->_db->{$collection}->Add(json_decode(json_encode($model)));

		unset($pk, $collection);

		return $this->_transaction() ? $model : null;
	}

	/**
	 * @param IQuarkModel $model
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Save (IQuarkModel $model, $options = []) {
		$collection = $this->_collection($model);

		$pk = $this->PrimaryKey($model)->Key();
		$new = false;

		if (!isset($model->$pk) || !is_scalar($model->$pk)) {
			$model->$pk = Quark::GuID();
			$new = true;
		}

		if ($new) $this->_db->{$collection}->Add($model);
		else {
			$model->$pk = (string)$model->$pk;

			$this->_db->{$collection}->Change(array(
				$pk => $model->$pk
			), $model);
		}

		return $this->_transaction() ? $model : null;
	}

	/**
	 * @param IQuarkModel $model
	 * @param $options
	 *
	 * @return mixed
	 *
	 * @throws QuarkArchException
	 */
	public function Remove (IQuarkModel $model, $options = []) {
		$collection = $this->_collection($model);

		$pk = $this->PrimaryKey($model)->Key();

		if (!isset($model->$pk))
			throw new QuarkArchException('Model ' . get_class($model) . ' does not have a primary key. Operation `remove` can not be executed');

		$this->_db->{$collection}->Purge(array(
			$pk => $model->$pk
		));

		return $this->_transaction();
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return QuarkKeyValuePair
	 */
	public function PrimaryKey (IQuarkModel $model) {
		return new QuarkKeyValuePair($model instanceof IQuarkModelWithCustomPrimaryKey ? $model->PrimaryKey() : '_id', '');
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return array
	 */
	private function _find (IQuarkModel $model, $criteria, $options) {
		$collection = $this->_collection($model, $options);
		
		return $this->_db->{$collection}->Select($criteria, $options)->Extract();
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return array
	 */
	public function Find (IQuarkModel $model, $criteria, $options) {
		return $this->_find($model, $criteria, $options);
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
		
		$records = $this->_find($model, $criteria, $options);
		
		return sizeof($records) == 0 ? null : $records[0];
	}

	/**
	 * @param IQuarkModel $model
	 * @param $id
	 * @param $options
	 *
	 * @return mixed
	 *
	 * @throws QuarkArchException
	 */
	public function FindOneById (IQuarkModel $model, $id, $options) {
		$pk = $this->PrimaryKey($model);
		
		return $this->FindOne($model, array(
			$pk->Key() => $id
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
		$collection = $this->_collection($model, $options);
		
		$count = $this->_db->{$collection}->Change($criteria, $model, $options);

		return $this->_transaction() ? $count : null;
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Delete (IQuarkModel $model, $criteria, $options) {
		$collection = $this->_collection($model, $options);
		
		$count = $this->_db->{$collection}->Purge($criteria, $options);

		return $this->_transaction() ? $count : null;
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
		$collection = $this->_collection($model, $options);
		
		return $this->_db->{$collection}->Count($criteria, $options);
	}

	/**
	 * @return mixed
	 */
	public function GuIDRequest () {
		return Quark::GuID();
	}
}