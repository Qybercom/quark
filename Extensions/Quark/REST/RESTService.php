<?php
namespace Quark\Extensions\Quark\REST;

use Quark\IQuarkDataProvider;
use Quark\IQuarkExtension;
use Quark\IQuarkModel;

use Quark\Quark;
use Quark\QuarkArchException;
use Quark\QuarkClient;
use Quark\QuarkDTO;
use Quark\QuarkHTTPTransport;
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
	 * @var string $_token
	 */
	private $_token = '';

	/**
	 * @var IQuarkRESTServiceDescriptor
	 */
	private $_descriptor = null;

	/**
	 * @param QuarkURI $uri
	 *
	 * @return mixed
	 */
	public function Connect (QuarkURI $uri) {
		$this->_uri = $uri;
	}

	/**
	 * @return QuarkURI
	 */
	public function SourceURI () {
		return $this->_uri;
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
		$this->_uri->query = 'access=' . $this->_token;

		$client = new QuarkClient($this->_uri->URI(true));
		$client->Transport(new QuarkHTTPTransport($request, $response));

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
			$data = $this->_api('POST', '/user/login', $criteria);

			$this->_token = $data->access;

			return $data->profile;
		}
		catch (QuarkArchException $e) {
			return false;
		}
	}

	/**
	 * @param $request
	 *
	 * @return bool
	 */
	public function Reconnect ($request) {
		if (!isset($request->access)) return false;

		$this->_token = $request->access;

		return true;
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
			$api = $this->_api('POST', '/' . self::_class($model) . '/update/' . $this->_descriptor->IdentifyModel($model), $model);

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
			$api = $this->_api('GET', '/' . self::_class($model) . '/remove/' . $this->_descriptor->IdentifyModel($model));

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
			$api = $this->_api('GET', '/' . self::_class($model) . '/' . $command . '/' . $this->_descriptor->IdentifyModel($model));

			return isset($api->status) && $api->status == 200;
		}
		catch (QuarkArchException $e) {
			return false;
		}
	}
}