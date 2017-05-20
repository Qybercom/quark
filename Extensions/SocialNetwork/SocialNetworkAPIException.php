<?php
namespace Quark\Extensions\SocialNetwork;

use Quark\Quark;
use Quark\QuarkArchException;
use Quark\QuarkDTO;

/**
 * Class SocialNetworkAPIException

 *
*@package Quark\Extensions\SocialNetwork
 */
class SocialNetworkAPIException extends QuarkArchException {
	/**
	 * @var QuarkDTO $_request
	 */
	private $_request;

	/**
	 * @var QuarkDTO $_response
	 */
	private $_response;

	/**
	 * @param QuarkDTO $request
	 * @param QuarkDTO $response
	 */
	public function __construct (QuarkDTO $request, QuarkDTO $response) {
		parent::__construct('', Quark::LOG_WARN);

		$this->_request = $request;
		$this->_response = $response;
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
}