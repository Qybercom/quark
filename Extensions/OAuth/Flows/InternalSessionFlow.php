<?php
namespace Quark\Extensions\OAuth\Flows;

use Quark\IQuarkAuthorizableModel;

use Quark\QuarkDTO;
use Quark\QuarkJSONIOProcessor;

use Quark\Extensions\OAuth\IQuarkOAuthFlow;
use Quark\Extensions\OAuth\OAuthError;
use Quark\Extensions\OAuth\OAuthFlowBehavior;
use Quark\Extensions\OAuth\OAuthToken;
use Quark\QuarkModel;

/**
 * Class InternalSessionFlow
 *
 * @package Quark\Extensions\OAuth\Flows
 */
class InternalSessionFlow implements IQuarkOAuthFlow {
	use OAuthFlowBehavior;

	/**
	 * @var IQuarkAuthorizableModel $_user
	 */
	private $_user;

	/**
	 * @param IQuarkAuthorizableModel $user = null
	 */
	public function __construct (IQuarkAuthorizableModel $user = null) {
		$this->_user = $user;
	}

	/**
	 * @param QuarkDTO $request
	 *
	 * @return bool
	 */
	public function OAuthFlowRecognize (QuarkDTO $request) {
		return false;
	}

	/**
	 * @return bool
	 */
	public function OAuthFlowRequiresAuthentication () {
		return false;
	}

	/**
	 * @param OAuthToken $token
	 *
	 * @return QuarkDTO|OAuthError
	 */
	public function OAuthFlowSuccess (OAuthToken $token) {
		$response = QuarkDTO::ForResponse(new QuarkJSONIOProcessor());
		$response->Data($token->ExtractOAuth());

		return $response;
	}

	/**
	 * @return string
	 */
	public function OAuthFlowModelProcessMethod () {
		return 'OAuthFlowInternalSession';
	}

	/**
	 * @return QuarkModel|IQuarkAuthorizableModel
	 */
	public function OAuthFlowUser () {
		return new QuarkModel($this->_user);
	}
}