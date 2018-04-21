<?php
namespace Quark\Extensions\OAuth;

use Quark\IQuarkAuthorizableModel;
use Quark\IQuarkAuthorizationProvider;

use Quark\Quark;
use Quark\QuarkArchException;
use Quark\QuarkDTO;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkKeyValuePair;
use Quark\QuarkModel;
use Quark\QuarkObject;
use Quark\QuarkSession;

use Quark\Extensions\OAuth\Flows\AuthorizationCodeFlow;
use Quark\Extensions\OAuth\Flows\ClientCredentialsFlow;
use Quark\Extensions\OAuth\Flows\ImplicitFlow;
use Quark\Extensions\OAuth\Flows\PasswordCredentialsFlow;
use Quark\Extensions\OAuth\Flows\RefreshTokenFlow;
use Quark\Extensions\OAuth\Flows\DeviceCodeFlow;
use Quark\Extensions\OAuth\Flows\InternalSessionFlow;

/**
 * Class OAuthServer
 *
 * https://tools.ietf.org/html/draft-ietf-oauth-v2-13
 *
 * @package Quark\Extensions\OAuth
 */
class OAuthServer implements IQuarkAuthorizationProvider {
	/**
	 * @var string $_session = ''
	 */
	private $_session = '';

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
	 * @var OAuthToken $_token
	 */
	private $_token;

	/**
	 * @var OAuthError $_error
	 */
	private $_error;

	/**
	 * @param string $session = ''
	 * @param IQuarkOAuthFlow[] $flows = []
	 */
	public function __construct ($session = '', $flows = []) {
		$this->_session = $session;
		$this->_flows = $flows;
	}

	/**
	 * @return IQuarkOAuthFlow
	 */
	public function OAuthFlow () {
		return $this->_flow;
	}

	/**
	 * @param IQuarkOAuthAuthorizableModel $model
	 * @param IQuarkOAuthFlow $flow = null
	 *
	 * @return mixed
	 */
	public function OAuthFlowProcess (IQuarkOAuthAuthorizableModel $model, IQuarkOAuthFlow $flow = null) {
		$method = $flow->OAuthFlowModelProcessMethod();

		if (!method_exists($model, $method)) {
			Quark::Log('[OAuthServer] Suggested method ' . $method . ' was not found in model ' . QuarkObject::ClassOf($model), Quark::LOG_WARN);

			return null;
		}

		return $model->$method($flow);
	}

	/**
	 * @param bool $dto = true
	 *
	 * @return QuarkDTO|OAuthError
	 */
	public function OAuthError ($dto = true) {
		if ($this->_error == null)
			$this->_error = new OAuthError(OAuthError::INVALID_REQUEST, 'Unsupported authorization flow');

		if (!$dto)
			return $this->_error;

		$response = QuarkDTO::ForStatus(QuarkDTO::STATUS_400_BAD_REQUEST);
		$response->Processor(new QuarkJSONIOProcessor());
		$response->Data((object)array(
			'error' => $this->_error->Error()
		));

		if ($this->_error->Description()) $response->error_description = $this->_error->Description();
		if ($this->_error->URI()) $response->error_uri = $this->_error->URI();
		if ($this->_error->State()) $response->state = $this->_error->State();

		return $response;
	}

	/**
	 * @param bool $dto = true
	 *
	 * @return QuarkDTO|OAuthToken
	 */
	public function OAuthSuccess ($dto = true) {
		if (!($this->_token instanceof OAuthToken)) {
			$this->_error = new OAuthError(OAuthError::INVALID_REQUEST);
			return $this->OAuthError($dto);
		}

		$out = $this->_flow->OAuthFlowSuccess($this->_token);

		if ($out instanceof QuarkDTO) return $dto ? $out : $this->_token;

		$this->_error = $out;

		return $this->OAuthError($dto);
	}

	/**
	 * @param string $config = ''
	 * @param QuarkSession $session = null
	 *
	 * @return OAuthToken|OAuthError
	 */
	public static function InternalSession ($config = '', QuarkSession $session = null) {
		if ($session == null) return null;

		$user = $session->User();
		if ($user == null) return null;

		$oauth = QuarkSession::Init($config, new QuarkDTO());
		$flow = new InternalSessionFlow($user->Model());

		/**
		 * @var OAuthServer $server
		 */
		$server = $oauth->Source()->Provider();

		if (!$oauth->Login($flow))
			return $server->OAuthError(false);

		$server->_flow = $flow;

		return $server->OAuthSuccess(false);
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

		$session = QuarkSession::Init($this->_session, $input);

		foreach ($this->_flows as $i => &$flow) {
			if (!$flow->OAuthFlowRecognize($this->_input)) continue;

			$this->_flow = $flow;

			if ($session != null)
				$this->_flow->OAuthFlowSession($session);
		}

		$output = new QuarkDTO();
		$output->AuthorizationProvider($this->_input->AuthorizationProvider());
		$output->Data(new OAuthFlow($this->_flow, $input->Authorization()));

		return $output;
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

		$this->_token = $model->OAuthModelToken();
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
	 * @param QuarkKeyValuePair $id
	 * @param $data
	 * @param bool $commit
	 *
	 * @return bool
	 */
	public function SessionData ($name, QuarkKeyValuePair $id, $data, $commit) {
		// TODO: Implement SessionData() method.
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
				new RefreshTokenFlow(),
				new DeviceCodeFlow()
			);
		}
	}

	/**
	 * @param $target = null
	 *
	 * @return bool
	 */
	private static function _oAuthCheck ($target = null) {
		return $target instanceof IQuarkOAuthAuthorizableModel;
	}

	/**
	 * @param QuarkModel|IQuarkOAuthAuthorizableModel $app = null
	 * @param $fallback = []
	 * @param string[] $scope = []
	 *
	 * @return OAuthError|bool
	 *
	 * @throws QuarkArchException
	 */
	public static function AuthenticationCriteria ($app = null, $fallback = [], $scope = []) {
		if ($app == null || !self::_oAuthCheck($app instanceof QuarkModel ? $app->Model() : $app)) return $fallback;

		$error = $app->OAuthModelError();
		if ($error) return $error;

		if (!$app->OAuthModelCheckScope($scope))
			return new OAuthError(OAuthError::ACCESS_DENIED);

		return true;
	}

	/**
	 * @param $criteria = null
	 * @param $fallback = []
	 *
	 * @return QuarkDTO|mixed
	 */
	public static function AuthenticationFailed ($criteria = null, $fallback = []) {
		return $criteria instanceof OAuthError ? $criteria->DTO() : $fallback;
	}
}