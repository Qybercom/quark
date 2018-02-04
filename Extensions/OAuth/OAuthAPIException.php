<?php
namespace Quark\Extensions\OAuth;

use Quark\Quark;
use Quark\QuarkArchException;
use Quark\QuarkDTO;

/**
 * Class OAuthAPIException
 *
 * @package Quark\Extensions\OAuth
 */
class OAuthAPIException extends QuarkArchException {
	/**
	 * @var QuarkDTO $_request
	 */
	private $_request;

	/**
	 * @var QuarkDTO $_response
	 */
	private $_response;

	/**
	 * @var OAuthError $_error
	 */
	private $_error;

	/**
	 * @param QuarkDTO $request
	 * @param QuarkDTO $response
	 * @param OAuthError $error = null
	 */
	public function __construct (QuarkDTO $request, QuarkDTO $response, OAuthError $error = null) {
		parent::__construct('', Quark::LOG_WARN);

		$this->_request = $request;
		$this->_response = $response;
		$this->_error = $error;
	}

	/**
	 * @return QuarkDTO
	 */
	public function &Request () {
		return $this->_request;
	}

	/**
	 * @return QuarkDTO
	 */
	public function &Response () {
		return $this->_response;
	}

	/**
	 * @return OAuthError
	 */
	public function &Error () {
		return $this->_error;
	}
}