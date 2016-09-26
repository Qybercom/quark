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

	/**
	 * @var \stdClass $_db
	 */
	private $_db;

	/**
	 * @param IQuarkModel $model
	 * @param $options
	 *
	 * @return string
	 */
	private function _collection (IQuarkModel $model, $options = []) {
		$collection = QuarkModel::CollectionName($model, $options);

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
	 * @return void
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
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Create (IQuarkModel $model, $options = []) {
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
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Save (IQuarkModel $model, $options = []) {
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
	 * @param $criteria
	 * @param $options
	 * @param bool|true $one
	 *
	 * @return array|null
	 */
	private function _find (IQuarkModel $model, $criteria, $options, $one = true) {
		$collection = $this->_collection($model, $options);
		$pk = $this->PrimaryKey($model)->Key();
		$query = new QuarkDNAQuery($criteria);
		$out = $one ? null : array();

		foreach ($this->_db->$collection as $i => &$document) {
			if (!isset($document->$pk)) continue;
			if (!QuarkDNAID::IsValid($document->$pk)) continue;

			$document->$pk = new QuarkDNAID($document->$pk);

			if (!$query->Match($document, $options)) continue;

			if (!$one) $out[] = $document;
			else {
				$out = $document;
				break;
			}
		}

		unset($i, $document, $query, $collection);

		return $out;
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param             $options
	 *
	 * @return array
	 */
	public function Find (IQuarkModel $model, $criteria, $options) {
		return $this->_find($model, $criteria, $options, false);
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param             $options
	 *
	 * @return mixed
	 */
	public function FindOne (IQuarkModel $model, $criteria, $options) {
		return $this->_find($model, $criteria, $options);
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
		return $this->_find(
			$model,
			array($this->PrimaryKey($model)->Key() => $id),
			$options
		);
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
	 * @param int $length = self::SHORT_ID
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
	 * @param string $id
	 *
	 * @return bool
	 */
	public static function IsValid ($id) {
		return is_scalar($id) || (is_object($id) && method_exists($id, '__toString'));
	}

	/**
	 * @param string $id
	 * @param int $length = self::SHORT_ID
	 *
	 * @return bool
	 */
	public function Eq ($id, $length = self::SHORT_ID) {
		return $this->_id == $id || $this->Short($length) == $id;
	}
}

/**
 * Class QuarkDNAQuery
 *
 * @package Quark\DataProviders
 */
class QuarkDNAQuery {
	/**
	 * @var array $_query
	 */
	private $_query;

	/**
	 * @param array $query
	 */
	public function __construct ($query) {
		$this->_query = $query;
	}

	/**
	 * @param $document
	 * @param array $options
	 * @param array $query
	 *
	 * @return bool
	 */
	public function Match ($document, $options = [], $query = []) {
		$output = true;
		$query = func_num_args() == 3 ? $query : $this->_query;

		if (!is_array($query) || sizeof($query) == 0) return true;

		foreach ($query as $key => $rule) {
			$value = $rule;

			switch ($key) {
				case '$lte': $output &= $document <= $value; break;
				case '$lt': $output &= $document < $value; break;
				case '$gt': $output &= $document > $value; break;
				case '$gte': $output &= $document >= $value; break;
				case '$ne': $output &= $document != $value; break;

				case '$and':
					$value = $this->Match($rule, ' AND ');
					$output &= ' (' . $value . ') ';
					break;

				case '$or':
					$value = $this->Match($rule, ' OR ');
					$output &= ' (' . $value . ') ';
					break;

				case '$nor':
					$value = $this->Match($rule, ' NOT OR ');
					$output &= ' (' . $value . ') ';
					break;

				default:
					$field = eval('return $document->' . str_replace('.', '->', $key) . ';');
					$output &= is_string($key)
						? ($field instanceof QuarkDNAID
							? $field->Eq($value)
							: (is_array($rule)
								? $this->Match($field, $options, $rule)
								: $field == $value
							)
						)
						: false;
					break;
			}
		}

		return $output;
	}
}