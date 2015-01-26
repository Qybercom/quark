<?php
namespace Quark\DataProviders;

use Quark\IQuarkDataProvider;
use Quark\IQuarkModel;

use Quark\QuarkCredentials;

/**
 * Class PostgreSQL
 *
 * @package Quark\DataProviders
 */
class PostgreSQL implements IQuarkDataProvider {
	/**
	 * @param QuarkCredentials $credentials
	 *
	 * @return mixed
	 */
	public function Connect (QuarkCredentials $credentials) {
		// TODO: Implement Connect() method.
	}

	/**
	 * @return QuarkCredentials
	 */
	public function Credentials () {
		// TODO: Implement Credentials() method.
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
	 * @return IQuarkModel
	 */
	public function FindOne (IQuarkModel $model, $criteria) {
		// TODO: Implement FindOne() method.
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $id
	 *
	 * @return IQuarkModel
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