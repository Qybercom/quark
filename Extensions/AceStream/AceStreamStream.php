<?php
namespace Quark\Extensions\AceStream;

use Quark\QuarkURI;

/**
 * Class AceStreamStream
 *
 * @package Quark\Extensions\AceStream
 */
class AceStreamStream {
	/**
	 * @var string $_session = ''
	 */
	private $_session = '';

	/**
	 * @var bool $_live = false
	 */
	private $_live = false;

	/**
	 * @var QuarkURI $_uriPlayback
	 */
	private $_uriPlayback;

	/**
	 * @var QuarkURI $_uriCommand
	 */
	private $_uriCommand;

	/**
	 * @var QuarkURI $_uriStat
	 */
	private $_uriStat;

	/**
	 * @param string $session = ''
	 *
	 * @return string
	 */
	public function Session ($session = '') {
		if (func_num_args() != 0)
			$this->_session = $session;

		return $this->_session;
	}

	/**
	 * @param bool $live = false
	 *
	 * @return bool
	 */
	public function Live ($live = false) {
		if (func_num_args() != 0)
			$this->_live = $live;

		return $this->_live;
	}

	/**
	 * @param QuarkURI $uri = null
	 *
	 * @return QuarkURI
	 */
	public function &URIPlayback (QuarkURI $uri = null) {
		if (func_num_args() != 0)
			$this->_uriPlayback = $uri;

		return $this->_uriPlayback;
	}

	/**
	 * @param QuarkURI $uri = null
	 *
	 * @return QuarkURI
	 */
	public function &URICommand (QuarkURI $uri = null) {
		if (func_num_args() != 0)
			$this->_uriCommand = $uri;

		return $this->_uriCommand;
	}

	/**
	 * @param QuarkURI $uri = null
	 *
	 * @return QuarkURI
	 */
	public function &URIStat (QuarkURI $uri = null) {
		if (func_num_args() != 0)
			$this->_uriStat = $uri;

		return $this->_uriStat;
	}
}