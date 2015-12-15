<?php
namespace Quark\DataProviders;

use Quark\IQuarkDataProvider;
use Quark\IQuarkModel;
use Quark\IQuarkModelWithCustomPrimaryKey;

use Quark\Quark;
use Quark\QuarkArchException;
use Quark\QuarkFile;
use Quark\QuarkKeyValuePair;
use Quark\QuarkModel;
use Quark\QuarkObject;
use Quark\QuarkURI;

/**
 * Class QuarkDNA
 *
 * @package Quark\DataProviders
 */
class QuarkDNA implements IQuarkDataProvider {
	/**
	 * @var QuarkFile $_storage
	 */
	private $_storage;
	private $_db;

	/**
	 * @param IQuarkModel $model
	 *
	 * @return string
	 */
	private function _collection (IQuarkModel $model) {
		$collection = isset($options[QuarkModel::OPTION_COLLECTION])
			? $options[QuarkModel::OPTION_COLLECTION]
			: QuarkObject::ClassOf($model);

		if (sizeof((array)$this->_db) == 0)
			$this->_db = (object)array($collection => array());

		if (!isset($this->_db->$collection))
			$this->_db->$collection = array();

		return $collection;
	}

	/**
	 * @return bool
	 */
	private function _transaction () {
		$this->_storage->Content(json_encode($this->_db));

		return $this->_storage->SaveContent();
	}

	/**
	 * @param QuarkURI $uri
	 *
	 * @return mixed
	 *
	 * @throws QuarkArchException
	 */
	public function Connect (QuarkURI $uri) {
		if ($uri->path == null)
			throw new QuarkArchException('QuarkDNA: Database path cannot be empty');

		$this->_storage = new QuarkFile($uri->path);

		if ($this->_storage->Exists()) $this->_storage->Load();
		else {
			$this->_storage->Content('{}');
			$this->_storage->SaveContent();
		}

		$this->_db = json_decode($this->_storage->Content());
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return mixed
	 */
	public function Create (IQuarkModel $model) {
		$collection = $this->_collection($model);

		$pk = $this->PrimaryKey($model)->Key();

		$model->$pk = isset($model->$pk) ? $model->$pk: new QuarkDNAID();
		$model->$pk = (string)$model->$pk;

		$this->_db->{$collection}[] = $model;

		unset($pk, $collection);

		return $this->_transaction();
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return mixed
	 */
	public function Save (IQuarkModel $model) {
		$collection = $this->_collection($model);

		$pk = $this->PrimaryKey($model)->Key();
		$new = false;
		$ok = false;

		if (!isset($model->$pk)) {
			$model->$pk = new QuarkDNAID();
			$new = true;
		}

		if ($new) {
			$this->_db->{$collection}[] = $model;
			return $this->_transaction();
		}

		$model->$pk = (string)$model->$pk;

		foreach ($this->_db->$collection as $i => &$document) {
			if (!isset($document->$pk) || $document->$pk != (string)$model->$pk) continue;

			$this->_db->{$collection}[$i] = $model;

			$ok = $this->_transaction();
			break;
		}

		if (!$ok) {
			$this->_db->{$collection}[] = $model;
			$ok = $this->_transaction();
		}

		$model->$pk = new QuarkDNAID($model->$pk);

		unset($i, $document, $new, $pk, $collection);

		return $ok;
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return mixed
	 *
	 * @throws QuarkArchException
	 */
	public function Remove (IQuarkModel $model) {
		$collection = $this->_collection($model);

		$pk = $this->PrimaryKey($model)->Key();

		if (!isset($model->$pk))
			throw new QuarkArchException('Model ' . get_class($model) . ' doe not have a primary key. Operation `remove` can not be executed');

		$model->$pk = (string)$model->$pk;

		foreach ($this->_db->$collection as $i => &$document) {
			if (!isset($document->$pk) || $document->$pk != $model->$pk) continue;

			unset($this->_db->{$collection}[$i]);
			return $this->_transaction();
		}

		unset($i, $document, $pk, $collection);

		return false;
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return QuarkKeyValuePair
	 */
	public function PrimaryKey (IQuarkModel $model) {
		return new QuarkKeyValuePair($model instanceof IQuarkModelWithCustomPrimaryKey ? $model->PrimaryKey() : '_id', new QuarkDNAID());
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
	 *
	 * @throws QuarkArchException
	 */
	public function FindOneById (IQuarkModel $model, $id, $options) {
		$collection = $this->_collection($model);

		$pk = $this->PrimaryKey($model)->Key();

		foreach ($this->_db->$collection as $i => &$document) {
			if (!isset($document->$pk)) continue;
			if (!QuarkDNAID::IsValid($document->$pk)) continue;

			$document->$pk = new QuarkDNAID($document->$pk);

			if ($document->$pk == $id || $document->$pk->Short() == $id) return $document;
		}

		unset($i, $document, $pk, $collection);

		return null;
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
}

/**
 * Class QuarkDNAID
 *
 * @package Quark\DataProviders
 */
class QuarkDNAID {
	const SHORT_ID = 7;

	/**
	 * @var string $_id
	 */
	private $_id = '';

	/**
	 * @param string $id
	 *
	 * http://stackoverflow.com/a/9517767/2097055
	 *
	 * @throws QuarkArchException
	 */
	public function __construct ($id = '') {
		if (!self::IsValid($id))
			throw new QuarkArchException('QuarkDNAID allows only string as `$id` param. ' . print_r($id, true) . ' (' . gettype($id) . ') given');

		$this->_id = func_num_args() == 0 ? self::Generate() : (string)$id;
	}

	/**
	 * @return string
	 */
	public function __toString () {
		return $this->_id;
	}

	/**
	 * @param int $length
	 *
	 * @return string
	 */
	public function Short ($length = self::SHORT_ID) {
		return substr($this->_id, 0, $length);
	}

	/**
	 * @return string
	 */
	public static function Generate () {
		return Quark::GuID();
	}

	/**
	 * @param $id
	 *
	 * @return bool
	 */
	public static function IsValid ($id) {
		return is_scalar($id) || (is_object($id) && method_exists($id, '__toString'));
	}
}