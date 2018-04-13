<?php
namespace Quark\Extensions\OAuth;

use Quark\QuarkDTO;

/**
 * Class OAuthError
 *
 * @package Quark\Extensions\OAuth
 */
class OAuthError {
	const INTERNAL_ERROR = 'internal_error';

	const ACCESS_DENIED = 'access_denied';
	const UNAUTHORIZED_CLIENT = 'unauthorized_client';
	const INVALID_REQUEST = 'invalid_request';
	const INVALID_SCOPE = 'invalid_scope';
	const INVALID_CLIENT = 'invalid_client';
	const INVALID_GRANT = 'invalid_grant';
	const INVALID_TOKEN = 'invalid_token';
	const UNSUPPORTED_GRANT_TYPE = 'unsupported_grant_type';
	const UNSUPPORTED_RESPONSE_TYPE = 'unsupported_response_type';
	const SLOW_DOWN = 'slow_down';
	const AUTHORIZATION_PENDING = 'authorization_pending';

	/**
	 * @var string $_error = ''
	 */
	private $_error = '';

	/**
	 * @var string $_description = ''
	 */
	private $_description = '';

	/**
	 * @var string $_uri = ''
	 */
	private $_uri = '';

	/**
	 * @var string $_state = ''
	 */
	private $_state = '';

	/**
	 * @param string $error = ''
	 * @param string $description = ''
	 * @param string $uri = ''
	 * @param string $state = ''
	 */
	public function __construct ($error = '', $description = '', $uri = '', $state = '') {
		$this->Error($error);
		$this->Description($description);
		$this->URI($uri);
		$this->State($state);
	}

	/**
	 * @param string $error = ''
	 *
	 * @return string
	 */
	public function Error ($error = '') {
		if (func_num_args() != 0)
			$this->_error = $error;

		return $this->_error;
	}

	/**
	 * @param string $description = ''
	 *
	 * @return string
	 */
	public function Description ($description = '') {
		if (func_num_args() != 0)
			$this->_description = $description;

		return $this->_description;
	}

	/**
	 * @param string $uri = ''
	 *
	 * @return string
	 */
	public function URI ($uri = '') {
		if (func_num_args() != 0)
			$this->_uri = $uri;

		return $this->_uri;
	}

	/**
	 * @param string $state = ''
	 *
	 * @return string
	 */
	public function State ($state = '') {
		if (func_num_args() != 0)
			$this->_state = $state;

		return $this->_state;
	}

	/**
	 * @param string $status = QuarkDTO::STATUS_400_BAD_REQUEST
	 *
	 * @return QuarkDTO
	 */
	public function DTO ($status = QuarkDTO::STATUS_400_BAD_REQUEST) {
		$response = QuarkDTO::ForStatus($status);
		$response->Data((object)array(
			'error' => $this->_error
		));

		if ($this->_description) $response->error_description = $this->_description;
		if ($this->_uri) $response->error_uri = $this->_uri;
		if ($this->_state) $response->state = $this->_state;

		return $response;
	}

	/**
	 * @return object
	 */
	public function Data () {
		$data = (object)array(
			'error' => $this->_error
		);

		if ($this->_description) $data->error_description = $this->_description;
		if ($this->_uri) $data->error_uri = $this->_uri;
		if ($this->_state) $data->state = $this->_state;

		return $data;
	}
}