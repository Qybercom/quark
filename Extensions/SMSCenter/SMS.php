<?php
namespace Quark\Extensions\SMSCenter;

use Quark\IQuarkExtension;

use Quark\Quark;
use Quark\QuarkClient;
use Quark\QuarkDTO;
use Quark\QuarkHTTPTransportClient;
use Quark\QuarkArchException;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkPlainIOProcessor;

/**
 * Class SMS
 *
 * @package Quark\Extensions\SMSCenter
 */
class SMS implements IQuarkExtension {
	/**
	 * @var SMSCenterConfig $_config
	 */
	private $_config;

	private $_message = '';
	private $_phones = array();

	/**
	 * @var QuarkDTO $_response
	 */
	private $_response = null;

	/**
	 * @param string $config
	 * @param string $message
	 * @param array $phones
	 */
	public function __construct ($config, $message = '', $phones = []) {
		$this->Message($message);
		$this->Phones($phones);

		$this->_config = Quark::Config()->Extension($config);
	}

	/**
	 * @param string $message
	 *
	 * @return string
	 */
	public function Message ($message = '') {
		if (func_num_args() == 1)
			$this->_message = (string)$message;

		return $this->_message;
	}

	/**
	 * @param string $sender
	 *
	 * @return string
	 */
	public function Sender ($sender = '') {
		if (func_num_args() == 1)
			$this->_config->sender = $sender;

		return $this->_config->sender;
	}

	/**
	 * @param string $phone
	 */
	public function Phone ($phone) {
		$this->_phones[] = $phone;
	}

	/**
	 * @param array $phones
	 *
	 * @return array
	 */
	public function Phones ($phones = []) {
		if (func_num_args() == 1)
			$this->_phones = $phones;

		return $this->_phones;
	}

	/**
	 * @return bool
	 * @throws QuarkArchException
	 */
	public function Send () {
		return !isset($this->_main()->error);
	}

	/**
	 * @return float
	 * @throws QuarkArchException
	 */
	public function Cost () {
		return (float)$this->_main('&cost=1')->cost;
	}

	/**
	 * @return int
	 * @throws QuarkArchException
	 */
	public function Ping () {
		return $this->_main('&ping=1')->id;
	}

	/**
	 * @param string $append
	 *
	 * @return QuarkDTO
	 * @throws QuarkArchException
	 */
	private function _main ($append = '') {
		if (strlen($this->_message) == 0)
			throw new QuarkArchException('SMSCenter: message length should be greater than 0');

		$client = new QuarkClient(
			'http://smsc.ru/sys/send.php'
			. '?login='. $this->_config->username
			. '&psw=' . $this->_config->password
			. '&phones=' . implode(',', $this->_phones)
			. '&mes=' . $this->_message
			. '&fmt=3'
			. '&charset=utf-8'
			. ($this->_config->sender != '' ? '&sender=' . $this->_config->sender : '')
			. $append,
			new QuarkHTTPTransportClient(
				QuarkDTO::ForGET(new QuarkPlainIOProcessor()),
				new QuarkDTO(new QuarkJSONIOProcessor())
			)
		);

		$this->_response = $client->Action();

		return $this->_response;
	}

	/**
	 * @return QuarkDTO
	 */
	public function Response () {
		return $this->_response;
	}
}