<?php
namespace Quark\Extensions\OAuth;

/**
 * Class OAuthError
 *
 * @package Quark\Extensions\OAuth
 */
class OAuthError {
	const UNAUTHORIZED_CLIENT = 'unauthorized_client';
	const ACCESS_DENIED = 'access_denied';
	const INTERNAL_ERROR = 'internal_error';

	const INVALID_REQUEST = 'invalid_request';
	const INVALID_SCOPE = 'invalid_scope';
	const INVALID_CLIENT = 'invalid_client';
	const INVALID_GRANT = 'invalid_grant';
	const UNSUPPORTED_GRANT_TYPE = 'unsupported_grant_type';
	const UNSUPPORTED_RESPONSE_TYPE = 'unsupported_response_type';

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
}