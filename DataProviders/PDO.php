<?php
namespace Quark\DataProviders;

use Quark\IQuarkDataProvider;
use Quark\IQuarkModel;

use Quark\QuarkURI;

/**
 * Class PDO
 *
 * @package Quark\DataProviders
 */
class PDO implements IQuarkDataProvider {
	/**
	 * @param QuarkURI $uri
	 *
	 * @return mixed
	 */
	public function Connect (QuarkURI $uri) {
		// TODO: Implement Connect() method.
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return mixed
	 */
	public function Create (IQuarkModel $model) {
		// TODO: Implement Create() method.
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return mixed
	 */
	public function Save (IQuarkModel $model) {
		// TODO: Implement Save() method.
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return mixed
	 */
	public function Remove (IQuarkModel $model) {
		// TODO: Implement Remove() method.
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 *
	 * @return array
	 */
	public function Find (IQuarkModel $model, $criteria) {
		// TODO: Implement Find() method.
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 *
	 * @return mixed
	 */
	public function FindOne (IQuarkModel $model, $criteria) {
		// TODO: Implement FindOne() method.
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $id
	 *
	 * @return mixed
	 */
	public function FindOneById (IQuarkModel $model, $id) {
		// TODO: Implement FindOneById() method.
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
	 *
	 * @return int
	 */
	public function Count (IQuarkModel $model, $criteria, $limit, $skip) {
		// TODO: Implement Count() method.
	}
}