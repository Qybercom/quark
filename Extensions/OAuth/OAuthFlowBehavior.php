<?php
namespace Quark\Extensions\OAuth;

use Quark\QuarkDTO;
use Quark\QuarkKeyValuePair;
use Quark\QuarkSession;

/**
 * Class OAuthFlowBehavior
 *
 * @package Quark\Extensions\OAuth
 */
trait OAuthFlowBehavior {
	/**
	 * @var QuarkKeyValuePair $_client
	 */
	private $_client;

	/**
	 * @var QuarkSession $_session
	 */
	private $_session;

	/**
	 * @var string[] $_scope = []
	 */
	private $_scope = array();

	/**
	 * @param QuarkDTO $request
	 */
	private function _oAuthFlowInit (QuarkDTO $request) {
		$this->OAuthFlowClient(new QuarkKeyValuePair($request->client_id, $request->client_secret));
		$this->OAuthFlowScope($request->scope);
	}

	/**
	 * @param QuarkKeyValuePair $client = null
	 *
	 * @return QuarkKeyValuePair
	 */
	public function OAuthFlowClient (QuarkKeyValuePair $client = null) {
		if (func_num_args() != 0)
			$this->_client = $client;

		return $this->_client;
	}
	/**
	 * @param QuarkSession $session = null
	 *
	 * @return QuarkSession
	 */
	public function OAuthFlowSession (QuarkSession $session = null) {
		if (func_num_args() != 0)
			$this->_session = $session;

		return $this->_session;
	}

	/**
	 * @param string|string[] $scope = ''
	 *
	 * @return string[]
	 */
	public function OAuthFlowScope ($scope = '') {
		if (func_num_args() != 0) {
			if (is_array($scope)) $this->_scope = $scope;
			else {
				$scope = trim($scope);

				if (strlen($scope) != 0)
					$this->_scope = explode(',', $scope);
			}
		}

		return $this->_scope;
	}
}