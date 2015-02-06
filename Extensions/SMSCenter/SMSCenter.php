<?php
namespace Quark\Extensions\SMSCenter;

use Quark\IQuarkExtension;

use Quark\QuarkClient;
use Quark\QuarkDTO;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkHTTPTransport;
use Quark\QuarkArchException;
use Quark\QuarkPlainIOProcessor;

/**
 * Class SMSCenter
 *
 * @package Quark\Extensions\SMSCenter
 */
class SMSCenter implements IQuarkExtension {
	private static $_username = '';
	private static $_password = '';

	private static $_sender = '';
	private $_from = '';

	private $_message = '';
	private $_phones = array();

	private $_response = null;

	public function Setup ($username, $password, $sender = '') {
		self::$_username = $username;
		self::$_password = $password;
		self::$_sender = $sender;
	}

	public function __construct ($message = '', $phones = []) {
		$this->Message($message);
		$this->Phones($phones);

		$this->_from = self::$_sender;
	}

	public function Message ($message = '') {
		if (func_num_args() == 1)
			$this->_message = (string)$message;

		return $this->_message;
	}

	public function Sender ($sender = '') {
		if (func_num_args() == 1)
			$this->_from = $sender;

		return $this->_from;
	}

	public function Phone ($phone) {
		$this->_phones[] = $phone;
	}

	public function Phones ($phones = []) {
		if (func_num_args() == 1)
			$this->_phones = $phones;

		return $this->_phones;
	}

	public function Send () {
		return $this->_main();
	}

	public function Cost () {
		return $this->_main('&cost=1');
	}

	public function Ping () {
		return $this->_main('&ping=1');
	}

	private function _main ($append = '') {
		if (strlen($this->_message) == 0)
			throw new QuarkArchException('SMSCenter: message length should be greater than 0');

		$client = new QuarkClient(
			'http://smsc.ru/sys/send.php'
			. '?login='. self::$_username
			. '&psw=' . self::$_password
			. '&phones=' . implode(',', $this->_phones)
			. '&mes=' . $this->_message
			. '&fmt=3'
			. '&charset=utf-8'
			. ($this->_from != '' ? '&sender=' . $this->_from : '')
			. $append,
			new QuarkHTTPTransport(QuarkDTO::ForGET(new QuarkPlainIOProcessor()), new QuarkDTO(new QuarkPlainIOProcessor()))
		);

		$this->_response = $client->Action();

		return !isset($this->_response->error);
	}

	public function Response () {
		return $this->_response;
	}
}