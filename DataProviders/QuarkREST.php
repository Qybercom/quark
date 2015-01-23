<?php
namespace Quark\DataProviders;

use Quark\IQuarkModel;
use Quark\IQuarkDataProvider;

use Quark\Quark;
use Quark\QuarkArchException;
use Quark\QuarkClient;
use Quark\QuarkDTO;
use Quark\QuarkCredentials;
use Quark\QuarkJSONIOProcessor;

/**
 * Class QuarkREST
 *
 * @package Quark\DataProviders
 */
class QuarkREST implements IQuarkDataProvider {
	/**
	 * @var QuarkCredentials
	 */
	private $_connection = null;

	/**
	 * @var IQuarkRESTProvider
	 */
	private $_provider = null;

	/**
	 * @param IQuarkRESTProvider $provider
	 */
	public function __construct (IQuarkRESTProvider $provider) {
		$this->_provider = $provider;
	}

	/**
	 * @param QuarkCredentials $credentials
	 * @param mixed $append
	 *
	 * @return mixed
	 */
	public function Connect (QuarkCredentials $credentials, $append = []) {
		$this->_connection = func_num_args() == 2
			? Quark::Normalize($this->_connection, $append)
			: $credentials;
	}

	/**
	 * @param string $method
	 * @param string $action
	 * @param mixed $data
	 *
	 * @return mixed
	 * @throws QuarkArchException
	 */
	private function _api ($method, $action, $data = []) {
		$request = new QuarkDTO();
		$request->Processor(new QuarkJSONIOProcessor());
		$request->Data($data);

		$response = new QuarkDTO();
		$response->Processor(new QuarkJSONIOProcessor());
		$response->Data(array(
			'status' => 403
		));

		$this->_connection->Resource($action . '/?access=' . $this->_connection->token);

		$connection = new QuarkClient($this->_connection, $request, $response);

		/**
		 * @var QuarkDTO $output
		 */
		$output = $connection->$method();

		if (!$output) return null;

		$data = $output->Data();

		if ($data == null || !isset($data->status) || !isset($data->access) || $data->status != 200)
			throw new QuarkArchException('QuarkRest API is not reachable');

		return $data;
	}

	/**
	 * @param mixed $criteria
	 *
	 * @return mixed
	 */
	public function Login ($criteria = []) {
		try {
			$data = $this->_api('POST', '/user/login', $criteria);
			$this->_connection->token = $data->access;

			return $data->profile;
		}
		catch (QuarkArchException $e) {
			return false;
		}
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return string
	 */
	private static function _class (IQuarkModel $model) {
		return strtolower(Quark::ClassOf($model));
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return mixed
	 */
	public function Create (IQuarkModel $model) {
		try {
			$api = $this->_api('POST', '/' . self::_class($model) . '/create', $model);

			return isset($api->status) && $api->status == 200;
		}
		catch (QuarkArchException $e) {
			return false;
		}
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return mixed
	 */
	public function Save (IQuarkModel $model) {
		try {
			$api = $this->_api('POST', '/' . self::_class($model) . '/update/' . $this->_provider->Id($model), $model);

			return isset($api->status) && $api->status == 200;
		}
		catch (QuarkArchException $e) {
			return false;
		}
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return mixed
	 */
	public function Remove (IQuarkModel $model) {
		try {
			$api = $this->_api('GET', '/' . self::_class($model) . '/remove/' . $this->_provider->Id($model));

			return isset($api->status) && $api->status == 200;
		}
		catch (QuarkArchException $e) {
			return false;
		}
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 *
	 * @return array
	 */
	public function Find (IQuarkModel $model, $criteria) {
		try {
			$api = $this->_api('Get', '/' . self::_class($model) . '/list', $criteria);

			return isset($api->list) ? $api->list : array();
		}
		catch (QuarkArchException $e) {
			return array();
		}
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 *
	 * @return IQuarkModel|null
	 */
	public function FindOne (IQuarkModel $model, $criteria) {
		$class = self::_class($model);

		try {
			return @$this->_api('Get', '/' . $class, $criteria)->$class;
		}
		catch (QuarkArchException $e) {
			return null;
		}
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $id
	 *
	 * @return IQuarkModel|null
	 */
	public function FindOneById (IQuarkModel $model, $id) {
		$class = self::_class($model);

		try {
			return @$this->_api('Get', '/' . $class . '/' . $id)->$class;
		}
		catch (QuarkArchException $e) {
			return null;
		}
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