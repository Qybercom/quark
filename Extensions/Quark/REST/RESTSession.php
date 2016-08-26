<?php
namespace Quark\Extensions\Quark\REST;

use Quark\IQuarkAuthorizableModel;
use Quark\IQuarkAuthorizableModelWithSessionKey;
use Quark\IQuarkAuthorizationProvider;
use Quark\IQuarkAuthorizationProviderWithFullOutputControl;

use Quark\QuarkDTO;
use Quark\QuarkKeyValuePair;

/**
 * Class RESTSession
 *
 * @package Quark\Extensions\Quark\REST
 */
class RESTSession implements IQuarkAuthorizationProvider, IQuarkAuthorizationProviderWithFullOutputControl {
	/**
	 * @param string $name
	 * @param IQuarkAuthorizableModel $model
	 * @param QuarkDTO $input
	 *
	 * @return QuarkDTO
	 */
	public function Session ($name, IQuarkAuthorizableModel $model, QuarkDTO $input) {
		$key = $model instanceof IQuarkAuthorizableModelWithSessionKey
			? $model->SessionKey()
			: 'access';

		$session = $input->AuthorizationProvider();

		$output = new QuarkDTO();
		$output->AuthorizationProvider($session);
		$output->$key = $session == null ? $input->$key : $session->Value();

		return $output;
	}

	/**
	 * @param string $name
	 * @param IQuarkAuthorizableModel $model
	 * @param $criteria
	 * @param $lifetime
	 *
	 * @return QuarkDTO
	 */
	public function Login ($name, IQuarkAuthorizableModel $model, $criteria, $lifetime) {
		$key = $model instanceof IQuarkAuthorizableModelWithSessionKey
			? $model->SessionKey()
			: 'access';

		$output = new QuarkDTO();
		$output->AuthorizationProvider(new QuarkKeyValuePair($name, $model->$key));
		$output->$key = $model->$key;

		return $output;
	}

	/**
	 * @param string $name
	 * @param IQuarkAuthorizableModel $model
	 * @param QuarkKeyValuePair $id
	 *
	 * @return QuarkDTO
	 */
	public function Logout ($name, IQuarkAuthorizableModel $model, QuarkKeyValuePair $id) {
		return new QuarkDTO();
	}

	/**
	 * @param string $name
	 * @param IQuarkAuthorizableModel $model
	 * @param QuarkKeyValuePair $id
	 *
	 * @return void
	 */
	public function SessionCommit ($name, IQuarkAuthorizableModel $model, QuarkKeyValuePair $id) {
		// TODO: Implement SessionCommit() method.
	}

	/**
	 * @param object $ini
	 *
	 * @return void
	 */
	public function SessionOptions ($ini) {
		// TODO: Implement SessionOptions() method.
	}
}