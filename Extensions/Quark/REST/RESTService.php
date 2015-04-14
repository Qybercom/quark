<?php
namespace Quark\Extensions\Quark\REST;

use Quark\IQuarkDataProvider;
use Quark\IQuarkExtension;
use Quark\IQuarkModel;

use Quark\IQuarkModelWithCustomPrimaryKey;
use Quark\Quark;
use Quark\QuarkArchException;
use Quark\QuarkClient;
use Quark\QuarkDTO;
use Quark\QuarkHTTPTransportClient;
use Quark\QuarkURI;
use Quark\QuarkJSONIOProcessor;

/**
 * Class RESTService
 *
 * @package Quark\Extensions\Quark\REST
 */
class RESTService implements IQuarkDataProvider, IQuarkExtension {
	/**
	 * @var QuarkURI
	 */
	private $_uri = null;

	/**
	 * @param QuarkURI $uri
	 *
	 * @return mixed
	 */
	public function Connect (QuarkURI $uri) {
		$this->_uri = $uri;
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
		$request = new QuarkDTO(new QuarkJSONIOProcessor());
		$request->Method($method);
		$request->Data($data);

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		$this->_uri->path = $action;

		$client = new QuarkClient($this->_uri->URI(true));
		$client->Transport(new QuarkHTTPTransportClient($request, $response));

		$data = $client->Action()->Data();

		if ($data == null || !isset($data->status) || $data->status != 200)
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
			return $this->_api('POST', '/user/login', $criteria)->profile;
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
	private function _pk (IQuarkModel $model) {
		return $model instanceof IQuarkModelWithCustomPrimaryKey
			? $model->PrimaryKey()
			: '_id';
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return string
	 */
	private function _identify (IQuarkModel $model) {
		$pk = $this->_pk($model);

		return $model->$pk;
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
			$class = self::_class($model);
			$pk = $this->_pk($model);

			$api = $this->_api('POST', '/' . $class . '/create', $model);

			if (!isset($api->status) || $api->status != 200) return false;

			$model->$pk = $model->$pk instanceof \MongoId
				? new \MongoId($api->$class->$pk)
				: $api->$class->$pk;

			return true;
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
			$api = $this->_api('POST', '/' . self::_class($model) . '/update/' . $this->_identify($model), $model);

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
			$api = $this->_api('GET', '/' . self::_class($model) . '/remove/' . $this->_identify($model));

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

	/**
	 * @param IQuarkModel $model
	 * @param string $command
	 *
	 * @return bool
	 */
	public function Command (IQuarkModel $model, $command) {
		try {
			$api = $this->_api('GET', '/' . self::_class($model) . '/' . $command . '/' . $this->_identify($model));

			return isset($api->status) && $api->status == 200;
		}
		catch (QuarkArchException $e) {
			return false;
		}
	}
}