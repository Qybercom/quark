<?php
namespace Quark\Extensions\Quark\REST;

use Quark\IQuarkDataProvider;
use Quark\IQuarkExtension;
use Quark\IQuarkModel;
use Quark\IQuarkModelWithCustomPrimaryKey;

use Quark\Quark;
use Quark\QuarkArchException;
use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkModel;
use Quark\QuarkObject;
use Quark\QuarkURI;
use Quark\QuarkJSONIOProcessor;

/**
 * Class RESTService
 *
 * @package Quark\Extensions\Quark\REST
 */
class RESTService implements IQuarkDataProvider, IQuarkExtension {
	const OPTION_PAGE = 'page';

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
		$request->Merge($data);

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		if (!$this->_uri)
			throw new QuarkArchException('QuarkRest API is not reachable. URI is not provided');

		$this->_uri->path = $action;
		$uri = $this->_uri->URI(true);

		/**
		 * @var \StdClass $data
		 */
		$data = QuarkHTTPClient::To($uri, $request, $response);

		if ($data == null || !isset($data->status) || $data->status != 200)
			throw new QuarkArchException('[' . $uri . '] QuarkRest API is not reachable. Response: ' . print_r($data, true));

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
			Quark::Log($e->message, $e->lvl);
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

		return isset($model->$pk) ? $model->$pk : null;
	}

	/**
	 * http://php.net/manual/ru/function.preg-split.php#104602
	 *
	 * @param IQuarkModel $model
	 *
	 * @return string
	 */
	private static function _class (IQuarkModel $model) {
		$words = preg_split('/([[:upper:]][[:lower:]]+)/', QuarkObject::ClassOf($model), null, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);

		if (sizeof($words) == 1)
			$words[0] = strtolower($words[0]);

		return implode('/', $words);
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
			Quark::Log($e->message, $e->lvl);
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
			Quark::Log($e->message . print_r($model, true), $e->lvl);
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
			Quark::Log($e->message, $e->lvl);
			return false;
		}
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return string
	 */
	public function PrimaryKey (IQuarkModel $model) {
		return $this->_pk($model);
	}

	/**
	 * @param IQuarkModel $model
	 * @param array $criteria
	 * @param array $options
	 *
	 * @return array
	 */
	public function Find (IQuarkModel $model, $criteria, $options = []) {
		try {
			if (isset($options[self::OPTION_PAGE]))
				$this->_uri->query .= '&page=' . $options[self::OPTION_PAGE];

			if (isset($options[QuarkModel::OPTION_LIMIT]))
				$this->_uri->query .= '&limit=' . $options[QuarkModel::OPTION_LIMIT];

			$api = $this->_api('Get', '/' . self::_class($model) . '/list', $criteria);

			return isset($api->list) ? $api->list : array();
		}
		catch (QuarkArchException $e) {
			Quark::Log($e->message, $e->lvl);
			return array();
		}
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return IQuarkModel|null
	 */
	public function FindOne (IQuarkModel $model, $criteria, $options) {
		$class = self::_class($model);

		try {
			return $this->_api('Get', '/' . $class, $criteria)->$class;
		}
		catch (QuarkArchException $e) {
			Quark::Log($e->message, $e->lvl);
			return null;
		}
	}

	/**
	 * @param IQuarkModel $model
	 * @param $id
	 * @param $options
	 *
	 * @return null
	 * @throws QuarkArchException
	 */
	public function FindOneById (IQuarkModel $model, $id, $options) {
		if (!is_scalar($id))
			throw new QuarkArchException('Parameter $id must have scalar value, Given: ' . print_r($id, true));

		$class = self::_class($model);

		try {
			return $this->_api('Get', '/' . $class . '/' . $id)->$class;
		}
		catch (QuarkArchException $e) {
			Quark::Log($e->message, $e->lvl);
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
	 * @param             $options
	 *
	 * @return int
	 */
	public function Count (IQuarkModel $model, $criteria, $limit, $skip, $options) {
		// TODO: Implement Count() method.
	}

	/**
	 * @param IQuarkModel $model
	 * @param string $command
	 * @param QuarkDTO|object|array $data
	 * @param string $method = 'GET'
	 *
	 * @return bool
	 */
	public function Command (IQuarkModel $model, $command, $data = [], $method = 'GET') {
		try {
			return $this->_api($method, '/' . self::_class($model) . '/' . $command . '/' . $this->_identify($model), $data);
		}
		catch (QuarkArchException $e) {
			Quark::Log($e->message, $e->lvl);
			return false;
		}
	}
}