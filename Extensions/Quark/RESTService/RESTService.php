<?php
namespace Quark\Extensions\Quark\RESTService;

use Quark\IQuarkDataProvider;
use Quark\IQuarkExtension;
use Quark\IQuarkConfigurableExtension;
use Quark\IQuarkExtensionConfig;
use Quark\IQuarkModel;

use Quark\Quark;
use Quark\QuarkArchException;
use Quark\QuarkClient;
use Quark\QuarkDTO;
use Quark\QuarkCredentials;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkModel;

/**
 * Class RESTService
 *
 * @package Quark\Extensions\Quark\RESTService
 */
class RESTService implements IQuarkDataProvider, IQuarkExtension, IQuarkConfigurableExtension {
	/**
	 * @var QuarkCredentials
	 */
	private $_connection = null;

	/**
	 * @var IQuarkRESTServiceDescriptor
	 */
	private $_descriptor = null;

	/**
	 * @param IQuarkExtensionConfig|Config $config
	 *
	 * @return mixed
	 */
	public function Init (IQuarkExtensionConfig $config) {
		$this->_descriptor = $config->Descriptor();

		Quark::Config()->DataProvider($config->Source(), $this, QuarkCredentials::FromURI($config->Endpoint()));
	}

	/**
	 * @param QuarkCredentials $credentials
	 *
	 * @return mixed
	 */
	public function Connect (QuarkCredentials $credentials) {
		$this->_connection = $credentials;
	}

	/**
	 * @return QuarkCredentials
	 */
	public function Credentials () {
		return $this->_connection;
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
	 * @param string $token
	 */
	public function Reconnect ($token) {
		$this->_connection->token = $token;
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
}