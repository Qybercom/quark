<?php
namespace Quark\Extensions\OAuth;

use Quark\IQuarkAuthorizableModel;
use Quark\IQuarkAuthorizationProvider;
use Quark\IQuarkModel;

use Quark\Quark;
use Quark\QuarkArchException;
use Quark\QuarkDTO;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkKeyValuePair;

use Quark\Extensions\OAuth\Flows\AuthorizationCodeFlow;
use Quark\Extensions\OAuth\Flows\ClientCredentialsFlow;
use Quark\Extensions\OAuth\Flows\ImplicitFlow;
use Quark\Extensions\OAuth\Flows\PasswordCredentialsFlow;
use Quark\Extensions\OAuth\Flows\RefreshTokenFlow;

/**
 * Class OAuthServer
 *
 * @package Quark\Extensions\OAuth
 */
class OAuthServer implements IQuarkAuthorizationProvider {
	/**
	 * @var IQuarkModel $_character
	 */
	private $_character;

	/**
	 * @var IQuarkOAuthFlow[] $_flows = []
	 */
	private $_flows = array();

	/**
	 * @var IQuarkOAuthFlow $_flow
	 */
	private $_flow;

	/**
	 * @var QuarkDTO $_input
	 */
	private $_input;

	/**
	 * @var OAuthToken $_success
	 */
	private $_success;

	/**
	 * @var OAuthError $_error
	 */
	private $_error;

	/**
	 * @param IQuarkModel $character = null
	 * @param IQuarkOAuthFlow[] $flows = []
	 */
	public function __construct (IQuarkModel $character = null, $flows = []) {
		$this->_character = $character;
		$this->_flows = $flows;
	}

	/**
	 * @param IQuarkModel $character = null
	 *
	 * @return IQuarkModel
	 */
	public function &Character (IQuarkModel $character = null) {
		if (func_num_args() != 0)
			$this->_character = $character;

		return $this->_character;
	}

	/**
	 * @param QuarkDTO $request = null
	 *
	 * @return IQuarkOAuthFlow
	 */
	public function OAuthFlow (QuarkDTO $request = null) {
		foreach ($this->_flows as $i => &$flow)
			if ($flow->OAuthFlowRecognize(func_num_args() == 0 ? $this->_input : $request))
				return $this->_flow = $flow;

		return null;
	}

	/**
	 * @param string $status = QuarkDTO::STATUS_400_BAD_REQUEST
	 *
	 * @return QuarkDTO
	 */
	public function OAuthError ($status = QuarkDTO::STATUS_400_BAD_REQUEST) {
		if ($this->_error == null)
			$this->_error = new OAuthError(OAuthError::INVALID_REQUEST, 'Unsupported authorization flow');

		$response = QuarkDTO::ForStatus($status);
		$response->Processor(new QuarkJSONIOProcessor());
		$response->Data((object)array(
			'error' => $this->_error->Error()
		));

		if ($this->_error->Description()) $response->error_description = $this->_error->Description();
		if ($this->_error->Uri()) $response->error_uri = $this->_error->Description();
		if ($this->_error->State()) $response->state = $this->_error->State();

		return $response;
	}

	/**
	 * @return QuarkDTO
	 */
	public function OAuthSuccess () {
		if (!($this->_success instanceof OAuthToken)) {
			$this->_error = new OAuthError(OAuthError::INVALID_REQUEST);
			return $this->OAuthError();
		}

		$out = $this->_flow->OAuthFlowSuccess($this->_success);

		if ($out instanceof QuarkDTO) return $out;

		$this->_error = $out;

		return $this->OAuthError();
	}

	/**
	 * @param string $name
	 * @param IQuarkAuthorizableModel $model
	 * @param QuarkDTO $input
	 *
	 * @return QuarkDTO
	 */
	public function Session ($name, IQuarkAuthorizableModel $model, QuarkDTO $input) {
		$this->_input = $input;
	}

	/**
	 * @param string $name
	 * @param IQuarkAuthorizableModel $model
	 * @param $criteria
	 * @param $lifetime
	 *
	 * @return QuarkDTO
	 *
	 * @throws QuarkArchException
	 */
	public function Login ($name, IQuarkAuthorizableModel $model, $criteria, $lifetime) {
		if (!($model instanceof IQuarkOAuthAuthorizableModel))
			throw new QuarkArchException('[OAuthServer::Login] Model of class ' . get_class($model) . ' is not a IQuarkOAuthAuthorizableModel');

		$this->_success = $model->OAuthModelSuccess();
		$this->_error = $model->OAuthModelError();

		if ($this->_error)
			return $this->OAuthError();

		$response = QuarkDTO::ForStatus(QuarkDTO::STATUS_200_OK);
		$response->Processor(new QuarkJSONIOProcessor());

		return $response;
	}

	/**
	 * @param string $name
	 * @param IQuarkAuthorizableModel $model
	 * @param QuarkKeyValuePair $id
	 *
	 * @return QuarkDTO
	 */
	public function Logout ($name, IQuarkAuthorizableModel $model, QuarkKeyValuePair $id) {
		// TODO: Implement Logout() method.
	}

	/**
	 * @param string $name
	 * @param IQuarkAuthorizableModel $model
	 * @param QuarkKeyValuePair $id
	 *
	 * @return bool
	 */
	public function SessionCommit ($name, IQuarkAuthorizableModel $model, QuarkKeyValuePair $id) {
		// TODO: Implement SessionCommit() method.
	}

	/**
	 * @param object $ini
	 *
	 * @return mixed
	 */
	public function SessionOptions ($ini) {
		if (!isset($ini->AutoDiscover) || $ini->AutoDiscover) {
			$this->_flows = array(
				new AuthorizationCodeFlow(),
				new ImplicitFlow(),
				new PasswordCredentialsFlow(),
				new ClientCredentialsFlow(),
				new RefreshTokenFlow()
			);
		}
	}
}